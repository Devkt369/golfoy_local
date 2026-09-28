<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;

/**
 * Service Get Movement Data From Source Deduction Request
 */
class GetMovementDataFromSourceDeductionRequest
{
    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    private $getSourceItemBySourceCodeAndSku;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetStockBySalesChannelInterface
     */
    private $getStockBySalesChannel;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var GetCurrentUserInterface
     */
    private $getCurrentUser;

    /**
     * GetMovementDataFromSourceDeductionRequest constructor.
     *
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetStockBySalesChannelInterface $getStockBySalesChannel
     * @param DateTime $dateTime
     * @param GetCurrentUserInterface $getCurrentUser
     */
    public function __construct(
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetStockBySalesChannelInterface $getStockBySalesChannel,
        DateTime $dateTime,
        GetCurrentUserInterface $getCurrentUser
    ) {
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
        $this->dateTime = $dateTime;
        $this->getCurrentUser = $getCurrentUser;
    }

    /**
     * Modify movement data
     *
     * @param SourceDeductionRequestInterface $sourceDeductionRequest
     * @param array $metaData
     * @param string $actionType
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(
        SourceDeductionRequestInterface $sourceDeductionRequest,
        array $metaData,
        string $actionType
    ) {
        $movements = [];
        $sourceCode = $sourceDeductionRequest->getSourceCode();
        $salesChannel = $sourceDeductionRequest->getSalesChannel();
        $salesEvent = $sourceDeductionRequest->getSalesEvent();

        $stockId = $this->getStockBySalesChannel->execute($salesChannel)->getStockId();

        $currentUser = $this->getCurrentUser->getCurrentUser();

        foreach ($sourceDeductionRequest->getItems() as $item) {
            $itemSku = $item->getSku();
            $qty = -$item->getQty();
            $stockItemConfiguration = $this->getStockItemConfiguration->execute(
                $itemSku,
                $stockId
            );

            if (!$stockItemConfiguration->isManageStock()) {
                //We don't need to Manage Stock
                continue;
            }

            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($sourceCode, $itemSku);

            $movement = [
                InventoryMovementInterface::ACTION_TYPE => $actionType,
                InventoryMovementInterface::SOURCE_CODE => $sourceCode,
                InventoryMovementInterface::IS_IN_STOCK => $sourceItem->getStatus(),
                InventoryMovementInterface::PRODUCT_SKU => $itemSku,
                InventoryMovementInterface::OLD_QTY => $sourceItem->getQuantity(),
                InventoryMovementInterface::CHANGE_QTY => $qty,
                InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL
            ];

            $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                $movement[InventoryMovementInterface::PRODUCT_SKU]
                . $movement[InventoryMovementInterface::SOURCE_CODE]
                . $movement[InventoryMovementInterface::CHANGE_QTY]
                . $this->dateTime->timestamp()
                . ($salesEvent ? $salesEvent->getObjectType() . $salesEvent->getObjectId() : '');

            if (count($metaData)) {
                $movement[InventoryMovementInterface::METADATA] = $metaData;
            }

            if ($currentUser && $currentUser->getUserId()) {
                $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
            }

            $movements[] = $movement;
        }

        return $movements;
    }

    /**
     * Modify movement data after execute
     *
     * @param array $movementsData
     * @param array $metaData
     * @return array
     */
    public function executeAfter(
        array $movementsData,
        array $metaData
    ) {
        foreach (array_keys($movementsData) as $key) {
            $movementsData[$key][InventoryMovementInterface::METADATA] = $metaData;
            $movementsData[$key][InventoryMovementInterface::NEW_QTY] =
                $movementsData[$key][InventoryMovementInterface::OLD_QTY] +
                $movementsData[$key][InventoryMovementInterface::CHANGE_QTY];
            $movementsData[$key][InventoryMovementInterface::STATUS] =
                InventoryMovementInterface::STATUS_SUCCESS;
        }
        return $movementsData;
    }
}
