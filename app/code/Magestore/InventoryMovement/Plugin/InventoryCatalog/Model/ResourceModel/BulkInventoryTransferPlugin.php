<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\InventoryCatalog\Model\ResourceModel;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventoryCatalog\Model\ResourceModel\BulkInventoryTransfer;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\FilterSourceItemInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BulkInventoryTransferPlugin
 *
 * Used for logging event transfer inventory
 */
class BulkInventoryTransferPlugin
{
    const ACTION_TYPE_MAGENTO_TRANSFER = 'magento_transfer';

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
     * @param BulkInventoryTransfer $subject
     * @param \Closure $process
     * @param array $skus
     * @param string $originSource
     * @param string $destinationSource
     * @param bool $unassignFromOrigin
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        BulkInventoryTransfer $subject,
        \Closure $process,
        array $skus,
        string $originSource,
        string $destinationSource,
        bool $unassignFromOrigin
    ): void {
        if ($this->skipMovementLog->getSkipMovementLog()) {
            $process($skus, $originSource, $destinationSource, $unassignFromOrigin);
            return;
        }

        $data = $this->prepareMovementsBeforeProcess($skus, $originSource, $destinationSource);
        $movements = $data["movements"];
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        if (count($movements)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }
        /* Process the function */
        $process($skus, $originSource, $destinationSource, $unassignFromOrigin);

        /* Save log after processing */
        foreach (array_keys($movements) as $key) {
            $movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
            $movements[$key][InventoryMovementInterface::NEW_QTY] = $data["newQtyData"][$key];
        }
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);
    }

    /**
     * Prepare Movements Before Process
     *
     * @param array $skus
     * @param string $originSource
     * @param string $destinationSource
     * @return array[]
     */
    public function prepareMovementsBeforeProcess(array $skus, string $originSource, string $destinationSource): array
    {
        $movements = [];
        $destinationMovements = [];
        $newQtyData = [];

        try {
            $currentUser = $this->getCurrentUser->getCurrentUser();
            $userId = null;
            $userName = null;
            $userType = null;
            if ($currentUser && $currentUser->getUserId()) {
                $userId = $currentUser->getUserId();
                $userType = $currentUser->getUserType();
                $userName = $currentUser->getUserName();
            }

            $orgSourceItems = $this->filterSourceItem->getBySkusAndSourceCodes($skus, [$originSource]);
            foreach ($orgSourceItems as $orgSourceItem) {
                // Log for origin source
                $movement = [
                    InventoryMovementInterface::PRODUCT_SKU => $orgSourceItem->getSku(),
                    InventoryMovementInterface::SOURCE_CODE => $orgSourceItem->getSourceCode(),
                    InventoryMovementInterface::OLD_QTY => $orgSourceItem->getQuantity(),
                    InventoryMovementInterface::CHANGE_QTY => -$orgSourceItem->getQuantity(),
                    InventoryMovementInterface::IS_IN_STOCK => 0,
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                    InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_MAGENTO_TRANSFER,
                    InventoryMovementInterface::USER_ID => $userId,
                    InventoryMovementInterface::USER_TYPE => $userType,
                    InventoryMovementInterface::USER_NAME => $userName,
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL => __("Magento Transfer Stock")
                    ]
                ];
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();

                $newQtyData[$movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID]] = 0;
                $movements[$movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID]] = $movement;

                // Log for destination source
                $movement = [
                    InventoryMovementInterface::PRODUCT_SKU => $orgSourceItem->getSku(),
                    InventoryMovementInterface::SOURCE_CODE => $destinationSource,
                    InventoryMovementInterface::OLD_QTY => 0,
                    InventoryMovementInterface::CHANGE_QTY => $orgSourceItem->getQuantity(),
                    InventoryMovementInterface::IS_IN_STOCK => $orgSourceItem->getStatus(),
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                    InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_MAGENTO_TRANSFER,
                    InventoryMovementInterface::USER_ID => $userId,
                    InventoryMovementInterface::USER_TYPE => $userType,
                    InventoryMovementInterface::USER_NAME => $userName,
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL => __("Magento Transfer Stock")
                    ]
                ];
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();

                $newQtyData[$movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID]] =
                    $orgSourceItem->getQuantity();
                $destinationMovements[$orgSourceItem->getSku()] = $movement;
            }

            $dstSourceItems = $this->filterSourceItem->getBySkusAndSourceCodes($skus, [$destinationSource]);
            foreach ($dstSourceItems as $dstSourceItem) {
                // Update new qty and status
                $sku = $dstSourceItem->getSku();
                if (isset($destinationMovements[$sku])) {
                    $destinationMovements[$sku][InventoryMovementInterface::OLD_QTY] = $dstSourceItem->getQuantity();
                    $newQtyData[$destinationMovements[$sku][InventoryMovementInterface::INVENTORY_MOVEMENT_ID]] =
                        $dstSourceItem->getQuantity()
                        + $destinationMovements[$sku][InventoryMovementInterface::CHANGE_QTY];
                }
            }

            foreach ($destinationMovements as $destinationMovement) {
                $movements[$destinationMovement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID]] =
                    $destinationMovement;
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return [
            "movements" => $movements,
            "newQtyData" => $newQtyData
        ];
    }
}
