<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventoryMovement\Plugin\Model\ResourceModel;

use Closure;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemCore;
use Magento\Inventory\Model\SourceItem as SourceItemModel;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\GetAreaAndUrlInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;

/**
 * Class SourceItem
 *
 * Plugin to log save source item event
 */
class SourceItem
{
    const ACTION_TYPE_SOURCE_ITEM_DELETED = 'source_item_deleted';
    const ACTION_TYPE_SOURCE_ITEM_CHANGED = 'source_item_changed';

    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    protected $getSourceItemBySourceCodeAndSku;

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
     * SourceItem constructor.
     *
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     * @param SkipMovementLogInterface $skipMovementLog
     * @param GetCurrentUserInterface $getCurrentUser
     * @param GetAreaAndUrlInterface $getAreaAndUrl
     */
    public function __construct(
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        DateTime $dateTime,
        EventManager $eventManager,
        SkipMovementLogInterface $skipMovementLog,
        GetCurrentUserInterface $getCurrentUser,
        GetAreaAndUrlInterface $getAreaAndUrl
    ) {
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
        $this->skipMovementLog = $skipMovementLog;
        $this->getCurrentUser = $getCurrentUser;
        $this->getAreaAndUrl = $getAreaAndUrl;
    }

    /**
     * Save source items
     *
     * @param SourceItemCore $subject
     * @param Closure $process
     * @param SourceItemModel $object
     * @return SourceItemCore
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        SourceItemCore $subject,
        Closure $process,
        SourceItemModel $object
    ) {
        if ($this->skipMovementLog->getSkipMovementLog() || !($object->getSku() && $object->getSourceCode())) {
            return $process($object);
        }

        $movementsDataLogs = [
            InventoryMovementInterface::PRODUCT_SKU => $object->getSku(),
            InventoryMovementInterface::SOURCE_CODE => $object->getSourceCode(),
            // Default set change qty = new qty (for save new source item)
            InventoryMovementInterface::CHANGE_QTY => $object->getQuantity(),
            InventoryMovementInterface::OLD_QTY => 0
        ];
        try {
            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($object->getSourceCode(), $object->getSku());
        } catch (\Exception $exception) {
            $sourceItem = null;
        }

        if ($sourceItem) {
            $movementsDataLogs[InventoryMovementInterface::OLD_QTY] = $sourceItem->getQuantity();
            $movementsDataLogs[InventoryMovementInterface::CHANGE_QTY]
                = $object->getQuantity() - $sourceItem->getQuantity();
        }

        if ($movementsDataLogs[InventoryMovementInterface::CHANGE_QTY]) {
            $areaAndUrl = $this->getAreaAndUrl->execute();
            $currentUser = $this->getCurrentUser->getCurrentUser();

            $movementsDataLogs[InventoryMovementInterface::METADATA] = [
                InventoryMovementInterface::ACTION_LABEL =>
                    __('Change Source Item') . '<br />' . __('Request: ')
                    . $areaAndUrl['area'] . ' - ' . $areaAndUrl['url']
            ];
            $movementsDataLogs[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                $movementsDataLogs[InventoryMovementInterface::PRODUCT_SKU]
                . $movementsDataLogs[InventoryMovementInterface::SOURCE_CODE]
                . $movementsDataLogs[InventoryMovementInterface::CHANGE_QTY]
                . $this->dateTime->timestamp();
            $movementsDataLogs[InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_FAIL;
            $movementsDataLogs[InventoryMovementInterface::ACTION_TYPE] = self::ACTION_TYPE_SOURCE_ITEM_CHANGED;
            $movementsDataLogs[InventoryMovementInterface::IS_IN_STOCK] = $object->getStatus();
            $movementsDataLogs[InventoryMovementInterface::CREATED_AT] = $this->dateTime->gmtDate();

            if ($currentUser) {
                $movementsDataLogs[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                $movementsDataLogs[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                $movementsDataLogs[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
            }

            // Save log before processing
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => [$movementsDataLogs]]);
        }

        /* Process the function */
        $result = $process($object);

        if ($movementsDataLogs[InventoryMovementInterface::CHANGE_QTY]) {
            $movementsDataLogs[InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
            $movementsDataLogs[InventoryMovementInterface::NEW_QTY] = $object->getQuantity();

            // Save log after processing
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => [$movementsDataLogs]]);
        }

        return $result;
    }

    /**
     * Around Delete
     *
     * @param SourceItemCore $subject
     * @param Closure $process
     * @param SourceItemModel $object
     * @return SourceItemCore
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDelete(
        SourceItemCore $subject,
        \Closure $process,
        SourceItemModel $object
    ) {
        if ($this->skipMovementLog->getSkipMovementLog() || !$object->getId()) {
            return $process($object);
        }

        $areaAndUrl = $this->getAreaAndUrl->execute();
        $currentUser = $this->getCurrentUser->getCurrentUser();

        $id = $object->getSku() . $object->getSourceCode() . -$object->getQuantity() . $this->dateTime->timestamp();
        $movement = [
            InventoryMovementInterface::INVENTORY_MOVEMENT_ID => $id,
            InventoryMovementInterface::PRODUCT_SKU => $object->getSku(),
            InventoryMovementInterface::SOURCE_CODE => $object->getSourceCode(),
            InventoryMovementInterface::OLD_QTY => $object->getQuantity(),
            InventoryMovementInterface::CHANGE_QTY => -$object->getQuantity(),
            InventoryMovementInterface::IS_IN_STOCK => 0,
            InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_SOURCE_ITEM_DELETED,
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

        // Save log before processing
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => [$movement]]);

        $this->eventManager->dispatch('inventory_movement_log_skip_default');

        /* Process the function */
        $result = $process($object);

        $movement[InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
        $movement[InventoryMovementInterface::NEW_QTY] = 0;

        // Save log after processing
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => [$movement]]);

        return $result;
    }
}
