<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\Catalog\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ProductPlugin
 *
 * Plugin for product resource model
 */
class ProductPlugin
{
    const ACTION_TYPE_PRODUCT_DELETED = 'product_deleted';

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var SkipMovementLogInterface
     */
    protected $skipMovementLog;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    protected $getSourceItemsBySku;

    /**
     * @var GetCurrentUserInterface
     */
    protected $getCurrentUser;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ProductPlugin constructor.
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     * @param SkipMovementLogInterface $skipMovementLog
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param GetCurrentUserInterface $getCurrentUser
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        DateTime $dateTime,
        EventManager $eventManager,
        SkipMovementLogInterface $skipMovementLog,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        GetCurrentUserInterface $getCurrentUser,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
        $this->skipMovementLog = $skipMovementLog;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->getCurrentUser = $getCurrentUser;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Around Delete
     *
     * @param ProductResourceModel $subject
     * @param \Closure $process
     * @param ProductInterface $object
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDelete(
        ProductResourceModel $subject,
        \Closure $process,
        ProductInterface $object
    ) {
        if ($this->skipMovementLog->getSkipMovementLog()) {
            return $process($object);
        }

        $movements = $this->prepareMovementsBeforeProcess($object->getSku());
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        if (count($movements)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }
        /* Process the function */
        $result = $process($object);

        /* Save log after processing */
        foreach (array_keys($movements) as $key) {
            $movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
            $movements[$key][InventoryMovementInterface::NEW_QTY] = 0;
        }
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        return $result;
    }

    /**
     * Prepare Movements Before Process
     *
     * @param string $productSku
     * @return array
     */
    public function prepareMovementsBeforeProcess(string $productSku)
    {
        $movements = [];

        if (!$productSku) {
            return $movements;
        }
        try {
            $sources = $this->getSourceItemsBySku->execute($productSku);
            if (!$sources || !count($sources)) {
                return $movements;
            }

            $product = $this->productRepository->get($productSku);
            $currentUser = $this->getCurrentUser->getCurrentUser();

            foreach ($sources as $source) {
                if (!$source->getQuantity()) {
                    continue;
                }
                $sourceCode = $source->getSourceCode();
                $movement = [
                    InventoryMovementInterface::PRODUCT_SKU => $product->getSku(),
                    InventoryMovementInterface::PRODUCT_ID => $product->getId(),
                    InventoryMovementInterface::PRODUCT_NAME => $product->getName(),
                    InventoryMovementInterface::SOURCE_CODE => $sourceCode,
                    InventoryMovementInterface::CHANGE_QTY => -$source->getQuantity(),
                    InventoryMovementInterface::OLD_QTY => $source->getQuantity(),
                    InventoryMovementInterface::IS_IN_STOCK => 0,
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                    InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_PRODUCT_DELETED,
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL => __("Delete Product")
                    ]
                ];
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();
                $movement[InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_FAIL;
                if ($currentUser) {
                    $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                    $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                    $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                }
                $movements[$sourceCode] = $movement;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $movements;
    }
}
