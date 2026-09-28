<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\InventoryImportExport\Plugin\Import;

use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryImportExport\Plugin\Import\SourceItemImporter as CoreSourceItemImporter;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\FilterSourceItemInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SourceItemImporterPlugin
 *
 * Plugin for importing source items after importing product
 */
class SourceItemImporterPlugin
{
    const ACTION_TYPE_IMPORT = 'import';

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
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSource
     */
    protected $defaultSource;

    /**
     * SourceItemImporterPlugin constructor.
     * @param FilterSourceItemInterface $filterSourceItem
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     * @param SkipMovementLogInterface $skipMovementLog
     * @param GetCurrentUserInterface $getCurrentUser
     * @param LoggerInterface $logger
     * @param DefaultSourceProviderInterface $defaultSource
     */
    public function __construct(
        FilterSourceItemInterface $filterSourceItem,
        DateTime $dateTime,
        EventManager $eventManager,
        SkipMovementLogInterface $skipMovementLog,
        GetCurrentUserInterface $getCurrentUser,
        LoggerInterface $logger,
        DefaultSourceProviderInterface $defaultSource
    ) {
        $this->filterSourceItem = $filterSourceItem;
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
        $this->skipMovementLog = $skipMovementLog;
        $this->getCurrentUser = $getCurrentUser;
        $this->logger = $logger;
        $this->defaultSource = $defaultSource;
    }

    /**
     * Around After Import
     *
     * @param CoreSourceItemImporter $corePlugin
     * @param \Closure $pluginProcess
     * @param StockItemImporterInterface $subject
     * @param mixed $result
     * @param array $stockData
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAfterImport(
        CoreSourceItemImporter $corePlugin,
        \Closure $pluginProcess,
        StockItemImporterInterface $subject,
        $result,
        array $stockData
    ) {
        if ($this->skipMovementLog->getSkipMovementLog()) {
            /* Process the function */
            $pluginProcess($subject, $result, $stockData);
            return;
        }

        $movementsData = $this->prepareMovementsBeforeProcess($stockData);
        $movements = $movementsData['movements'];
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        if (count($movements)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }
        /* Process the function */
        $pluginProcess($subject, $result, $stockData);

        /* Save log after processing */
        $movements = $this->prepareMovementsAfterProcess($movementsData);
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);
    }

    /**
     * Prepare Movements Before Process
     *
     * @param array $stockData
     * @return array[]
     */
    public function prepareMovementsBeforeProcess(array $stockData)
    {
        $movements = [];
        $newQtyData = [];

        try {
            $currentUser = $this->getCurrentUser->getCurrentUser();
            $userId = null;
            $userName = null;
            $userType = null;
            if ($currentUser) {
                $userId = $currentUser->getUserId();
                $userType = $currentUser->getUserType();
                $userName = $currentUser->getUserName();
            }

            foreach ($stockData as $sku => $stockDatum) {
                $inStock = (isset($stockDatum['is_in_stock'])) ? (int)($stockDatum['is_in_stock']) : 0;
                $qty = (isset($stockDatum['qty'])) ? (float)$stockDatum['qty'] : 0;
                $movement = [
                    InventoryMovementInterface::PRODUCT_SKU => $sku,
                    InventoryMovementInterface::SOURCE_CODE => $this->defaultSource->getCode(),
                    InventoryMovementInterface::CHANGE_QTY => $qty,
                    InventoryMovementInterface::OLD_QTY => 0,
                    InventoryMovementInterface::IS_IN_STOCK => $inStock,
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                    InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_IMPORT,
                    InventoryMovementInterface::USER_ID => $userId,
                    InventoryMovementInterface::USER_TYPE => $userType,
                    InventoryMovementInterface::USER_NAME => $userName,
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL => __("Magento Import")
                    ]
                ];
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();

                $movements[$sku] = $movement;
                $newQtyData[$sku] = $qty;
            }

            $skus = array_keys($stockData);
            $oldSourceItems = $this->filterSourceItem->getBySkusAndSourceCodes(
                $skus,
                [$this->defaultSource->getCode()]
            );

            foreach ($oldSourceItems as $sourceItem) {
                $sku = $sourceItem->getSku();
                if (isset($movements[$sku])) {
                    $movements[$sku][InventoryMovementInterface::OLD_QTY] = $sourceItem->getQuantity();
                    $movements[$sku][InventoryMovementInterface::CHANGE_QTY] =
                        $newQtyData[$sku] - $sourceItem->getQuantity();
                    if (!$movements[$sku][InventoryMovementInterface::CHANGE_QTY]) {
                        unset($movements[$sku]);
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return [
            'movements' => $movements,
            'newQtyData' => $newQtyData
        ];
    }

    /**
     * Prepare Movements After Process
     *
     * @param array $movementsData
     * @return array
     */
    public function prepareMovementsAfterProcess(array $movementsData)
    {
        $movements = isset($movementsData['movements']) ? $movementsData['movements'] : [];
        foreach (array_keys($movements) as $key) {
            $movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
            $movements[$key][InventoryMovementInterface::NEW_QTY] = $movementsData['newQtyData'][$key];
        }
        return $movements;
    }
}
