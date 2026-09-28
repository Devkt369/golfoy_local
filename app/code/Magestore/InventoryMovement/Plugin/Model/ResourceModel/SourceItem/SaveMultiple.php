<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventoryMovement\Plugin\Model\ResourceModel\SourceItem;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple as SaveMultipleCore;
use Magestore\InventoryMovement\Plugin\Model\ResourceModel\SourceItem;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\GetAreaAndUrlInterface;
use Magestore\InventoryMovementApi\Api\FilterSourceItemInterface;

/**
 * Plugin for log save
 *
 * Class SaveMultiple
 */
class SaveMultiple
{
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
     * @var GetAreaAndUrlInterface
     */
    protected $getAreaAndUrl;

    /**
     * @var FilterSourceItemInterface
     */
    protected $filterSourceItem;

    /**
     * SaveMultiple constructor.
     *
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     * @param SkipMovementLogInterface $skipMovementLog
     * @param GetCurrentUserInterface $getCurrentUser
     * @param GetAreaAndUrlInterface $getAreaAndUrl
     * @param FilterSourceItemInterface $filterSourceItem
     */
    public function __construct(
        DateTime $dateTime,
        EventManager $eventManager,
        SkipMovementLogInterface $skipMovementLog,
        GetCurrentUserInterface $getCurrentUser,
        GetAreaAndUrlInterface $getAreaAndUrl,
        FilterSourceItemInterface $filterSourceItem
    ) {
        $this->filterSourceItem = $filterSourceItem;
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
        $this->skipMovementLog = $skipMovementLog;
        $this->getCurrentUser = $getCurrentUser;
        $this->getAreaAndUrl = $getAreaAndUrl;
    }

    /**
     * Save movement log
     *
     * @param SaveMultipleCore $subject
     * @param \Closure $process
     * @param array $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        SaveMultipleCore $subject,
        \Closure $process,
        array $sourceItems
    ) {
        if ($this->skipMovementLog->getSkipMovementLog()) {
            return $process($sourceItems);
        }

        $movementsData = $this->getListMovements($sourceItems);
        $movements = $movementsData['movements'];

        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        /* Process the function */
        $process($sourceItems);

        /* Save log after processing */
        $movements = $this->prepareMovementsAfterProcess($movementsData);
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);
    }

    /**
     * Get List Movements
     *
     * @param array $sourceItems
     * @return array[]
     */
    public function getListMovements(array $sourceItems)
    {
        $sourceItemSkus = [];
        $sourceItemSourceCodes = [];
        $movements = [];
        $newQtyData = [];
        $areaAndUrl = $this->getAreaAndUrl->execute();
        $currentUser = $this->getCurrentUser->getCurrentUser();

        foreach ($sourceItems as $sourceItem) {
            $sourceItemSkus[] = $sourceItem->getSku();
            $sourceItemSourceCodes[] = $sourceItem->getSourceCode();
            if ($sourceItem->getSku() && $sourceItem->getSourceCode()) {
                $key = $sourceItem->getSku() . $sourceItem->getSourceCode();
                $id = $sourceItem->getSku()
                    . $sourceItem->getSourceCode()
                    . $sourceItem->getQuantity()
                    . $this->dateTime->timestamp();

                $newQtyData[$key] = $sourceItem->getQuantity();
                $movements[$key] = [
                    InventoryMovementInterface::INVENTORY_MOVEMENT_ID => $id,
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::PRODUCT_SKU => $sourceItem->getSku(),
                    InventoryMovementInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                    InventoryMovementInterface::OLD_QTY => 0,
                    // Default set change qty = new qty (for save new source item)
                    InventoryMovementInterface::CHANGE_QTY => $sourceItem->getQuantity(),
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL =>
                            __('Change Source Item').'<br />'. __('Request: ')
                            . $areaAndUrl['area']. ' - '.$areaAndUrl['url']
                    ],
                    InventoryMovementInterface::ACTION_TYPE => SourceItem::ACTION_TYPE_SOURCE_ITEM_CHANGED,
                    InventoryMovementInterface::IS_IN_STOCK => $sourceItem->getStatus(),
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate()
                ];

                if ($currentUser) {
                    $movements[$key][InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                    $movements[$key][InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                    $movements[$key][InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                }
            }
        }

        /* one query to database to get source item by sku and source */
        $sourceCollection = $this->filterSourceItem->getBySkusAndSourceCodes(
            $sourceItemSkus,
            $sourceItemSourceCodes
        );

        foreach ($sourceCollection as $source) {
            $key = $source->getSku() . $source->getSourceCode();
            if (isset($movements[$key])) {
                $movements[$key][InventoryMovementInterface::OLD_QTY] = $source->getQuantity();
                $movements[$key][InventoryMovementInterface::CHANGE_QTY] =
                    $newQtyData[$key] - $source->getQuantity();

                if (!$movements[$key][InventoryMovementInterface::CHANGE_QTY]) {
                    unset($movements[$key]);
                    unset($newQtyData[$key]);
                }
            }
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
