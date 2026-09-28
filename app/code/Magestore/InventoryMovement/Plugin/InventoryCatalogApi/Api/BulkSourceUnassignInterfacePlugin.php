<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\InventoryCatalogApi\Api;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\FilterSourceItemInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BulkSourceUnassignInterfacePlugin
 *
 * Plugin to log event unassign product from source
 */
class BulkSourceUnassignInterfacePlugin
{
    const ACTION_TYPE_SOURCE_UNASSIGN = 'source_unassign';

    /**
     * @var FilterSourceItemInterface
     */
    protected $filterSourceItem;

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
     * @var GetCurrentUserInterface
     */
    protected $getCurrentUser;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * BulkSourceUnassignInterfacePlugin constructor.
     * @param FilterSourceItemInterface $filterSourceItem
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     * @param SkipMovementLogInterface $skipMovementLog
     * @param GetCurrentUserInterface $getCurrentUser
     * @param LoggerInterface $logger
     */
    public function __construct(
        FilterSourceItemInterface $filterSourceItem,
        DateTime $dateTime,
        EventManager $eventManager,
        SkipMovementLogInterface $skipMovementLog,
        GetCurrentUserInterface $getCurrentUser,
        LoggerInterface $logger
    ) {
        $this->filterSourceItem = $filterSourceItem;
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
        $this->skipMovementLog = $skipMovementLog;
        $this->getCurrentUser = $getCurrentUser;
        $this->logger = $logger;
    }

    /**
     * Around Execute
     *
     * @param BulkSourceUnassignInterface $subject
     * @param \Closure $process
     * @param string[] $skus
     * @param string[] $sourceCodes
     * @return int
     * @throws \Magento\Framework\Validation\ValidationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        BulkSourceUnassignInterface $subject,
        \Closure $process,
        array $skus,
        array $sourceCodes
    ) {
        if ($this->skipMovementLog->getSkipMovementLog()) {
            return $process($skus, $sourceCodes);
        }

        $movements = $this->prepareMovementsBeforeProcess($skus, $sourceCodes);

        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        if (count($movements)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }
        /* Process the function */
        $result = $process($skus, $sourceCodes);

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
     * @param array $skus
     * @param array $sourceCodes
     * @return array
     */
    public function prepareMovementsBeforeProcess(array $skus, array $sourceCodes)
    {
        $movements = [];

        try {
            $sourceItems = $this->filterSourceItem->getBySkusAndSourceCodes($skus, $sourceCodes);
            if (empty($sourceItems)) {
                return $movements;
            }

            $currentUser = $this->getCurrentUser->getCurrentUser();

            foreach ($sourceItems as $sourceItem) {
                $movement = [
                    InventoryMovementInterface::PRODUCT_SKU => $sourceItem->getSku(),
                    InventoryMovementInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                    InventoryMovementInterface::CHANGE_QTY => -$sourceItem->getQuantity(),
                    InventoryMovementInterface::OLD_QTY => $sourceItem->getQuantity(),
                    InventoryMovementInterface::IS_IN_STOCK => 0,
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_SOURCE_UNASSIGN,
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL => __("Unassign From Source")
                    ]
                ];
                if ($currentUser && $currentUser->getUserId()) {
                    $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                    $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                    $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                }
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();
                $movements[] = $movement;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $movements;
    }
}
