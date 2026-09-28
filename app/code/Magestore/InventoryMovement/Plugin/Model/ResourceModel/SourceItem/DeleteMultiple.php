<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventoryMovement\Plugin\Model\ResourceModel\SourceItem;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple as DeleteMultipleCore;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\GetAreaAndUrlInterface;
use Magestore\InventoryMovement\Plugin\Model\ResourceModel\SourceItem as SourceItemResourcePlugin;

/**
 * Plugin for log delete multiple
 *
 * Class DeleteMultiple
 */
class DeleteMultiple
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
     * DeleteMultiple constructor.
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     * @param SkipMovementLogInterface $skipMovementLog
     * @param GetCurrentUserInterface $getCurrentUser
     * @param GetAreaAndUrlInterface $getAreaAndUrl
     */
    public function __construct(
        DateTime $dateTime,
        EventManager $eventManager,
        SkipMovementLogInterface $skipMovementLog,
        GetCurrentUserInterface $getCurrentUser,
        GetAreaAndUrlInterface $getAreaAndUrl
    ) {
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
        $this->skipMovementLog = $skipMovementLog;
        $this->getCurrentUser = $getCurrentUser;
        $this->getAreaAndUrl = $getAreaAndUrl;
    }

    /**
     * Around Execute
     *
     * @param DeleteMultipleCore $subject
     * @param \Closure $process
     * @param array $sourceItems
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        DeleteMultipleCore $subject,
        \Closure $process,
        array $sourceItems
    ) {
        if ($this->skipMovementLog->getSkipMovementLog() || !count($sourceItems)) {
            $process($sourceItems);
            return;
        }

        $movements = [];
        $areaAndUrl = $this->getAreaAndUrl->execute();
        $currentUser = $this->getCurrentUser->getCurrentUser();

        foreach ($sourceItems as $sourceItem) {
            $id = $sourceItem->getSku()
                . $sourceItem->getSourceCode()
                . -$sourceItem->getQuantity()
                . $this->dateTime->timestamp();
            $movement = [
                InventoryMovementInterface::INVENTORY_MOVEMENT_ID => $id,
                InventoryMovementInterface::PRODUCT_SKU => $sourceItem->getSku(),
                InventoryMovementInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                InventoryMovementInterface::OLD_QTY => $sourceItem->getQuantity(),
                InventoryMovementInterface::CHANGE_QTY => -$sourceItem->getQuantity(),
                InventoryMovementInterface::IS_IN_STOCK => 0,
                InventoryMovementInterface::ACTION_TYPE => SourceItemResourcePlugin::ACTION_TYPE_SOURCE_ITEM_DELETED,
                InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                InventoryMovementInterface::METADATA => [
                    InventoryMovementInterface::ACTION_LABEL =>
                        __('Delete Source Item') . '<br />' . __('Request: ')
                        . $areaAndUrl['area'] . ' - ' . $areaAndUrl['url'],
                ]
            ];
            if ($currentUser && $currentUser->getUserId()) {
                $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
            }

            $movements[] = $movement;
        }

        // Save log before processing
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        if (count($movements)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }

        /* Process the function */
        $process($sourceItems);

        foreach (array_keys($movements) as $key) {
            $movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
            $movements[$key][InventoryMovementInterface::NEW_QTY] = 0;
        }

        // Save log after processing
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);
    }
}
