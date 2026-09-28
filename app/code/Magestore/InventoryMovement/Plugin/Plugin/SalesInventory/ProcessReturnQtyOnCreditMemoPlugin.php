<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\Plugin\SalesInventory;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Plugin\SalesInventory\ProcessReturnQtyOnCreditMemoPlugin
    as CoreProcessReturnQtyOnCreditMemoPlugin;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\ProcessRefundItemsInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterfaceFactory;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesInventory\Model\Order\ReturnProcessor;
use Magestore\InventoryMovement\Model\GetMovementDataFromSourceDeductionRequest;
use Magestore\InventoryMovement\Model\ReturnProcessor\GetSourceDeductionRequestFromCreditmemo;
use Magestore\InventoryMovement\Plugin\Observer\DeductSourceItemQuantityOnRefundObserver;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;

/**
 * Class ProcessReturnQtyOnCreditMemoPlugin
 *
 * Process events and dispatch event to save movement
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessReturnQtyOnCreditMemoPlugin
{
    /**
     * @var GetSkuFromOrderItemInterface
     */
    private $getSkuFromOrderItem;

    /**
     * @var ItemsToRefundInterfaceFactory
     */
    private $itemsToRefundFactory;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var GetMovementDataFromSourceDeductionRequest
     */
    private $getMovementDataFromSourceDeductionRequest;

    /**
     * @var GetSourceDeductionRequestFromCreditmemo
     */
    private $getSourceDeductionRequestFromCreditmemo;

    /**
     * @var ProcessRefundItemsInterface
     */
    private $processRefundItems;

    /**
     * ProcessReturnQtyOnCreditMemoPlugin constructor.
     *
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param ItemsToRefundInterfaceFactory $itemsToRefundFactory
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param EventManager $eventManager
     * @param GetMovementDataFromSourceDeductionRequest $getMovementDataFromSourceDeductionRequest
     * @param GetSourceDeductionRequestFromCreditmemo $getSourceDeductionRequestFromCreditmemo
     * @param ProcessRefundItemsInterface $processRefundItems
     */
    public function __construct(
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        ItemsToRefundInterfaceFactory $itemsToRefundFactory,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        EventManager $eventManager,
        GetMovementDataFromSourceDeductionRequest $getMovementDataFromSourceDeductionRequest,
        GetSourceDeductionRequestFromCreditmemo $getSourceDeductionRequestFromCreditmemo,
        ProcessRefundItemsInterface $processRefundItems
    ) {
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemsToRefundFactory = $itemsToRefundFactory;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->eventManager = $eventManager;
        $this->getMovementDataFromSourceDeductionRequest = $getMovementDataFromSourceDeductionRequest;
        $this->getSourceDeductionRequestFromCreditmemo = $getSourceDeductionRequestFromCreditmemo;
        $this->processRefundItems = $processRefundItems;
    }

    /**
     * Around plugin
     *
     * @param CoreProcessReturnQtyOnCreditMemoPlugin $corePlugin
     * @param callable $pluginProceed
     * @param ReturnProcessor $subject
     * @param callable $proceed
     * @param CreditmemoInterface $creditmemo
     * @param OrderInterface $order
     * @param array $returnToStockItems
     * @param bool $isAutoReturn
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAroundExecute(
        CoreProcessReturnQtyOnCreditMemoPlugin $corePlugin,
        callable $pluginProceed,
        ReturnProcessor $subject,
        callable $proceed,
        CreditmemoInterface $creditmemo,
        OrderInterface $order,
        array $returnToStockItems = [],
        $isAutoReturn = false
    ) {
        // Core function
        $items = [];
        foreach ($creditmemo->getItems() as $item) {
            if ($isAutoReturn || in_array($item->getOrderItemId(), $returnToStockItems)) {
                $orderItem = $item->getOrderItem();
                $itemSku = $this->getSkuFromOrderItem->execute($orderItem);

                if ($this->isValidItem($itemSku, $orderItem->getProductType())) {
                    $qty = (float)$item->getQty();
                    $processedQty = $orderItem->getQtyInvoiced() - $orderItem->getQtyRefunded() + $qty;
                    $items[$itemSku] = [
                        'qty' => ($items[$itemSku]['qty'] ?? 0) + $qty,
                        'processedQty' => ($items[$itemSku]['processedQty'] ?? 0) + (float)$processedQty,
                    ];
                }
            }
        }

        $itemsToRefund = [];
        foreach ($items as $sku => $data) {
            $itemsToRefund[] = $this->itemsToRefundFactory->create(
                [
                    'sku' => $sku,
                    'qty' => $data['qty'],
                    'processedQty' => $data['processedQty'],
                ]
            );
        }

        $sourceDeductionRequests = $this->getSourceDeductionRequestFromCreditmemo->execute(
            $order,
            $itemsToRefund,
            $returnToStockItems
        );

        $metaData = [
            InventoryMovementInterface::ACTION_LABEL => __('New Refund For Order No'),
            InventoryMovementInterface::OBJECT_TYPE => 'order',
            InventoryMovementInterface::OBJECT_ID => $creditmemo->getOrderId(),
            InventoryMovementInterface::OBJECT_INCREMENT_ID => $order->getIncrementId()
        ];
        $movementsData = [];
        foreach ($sourceDeductionRequests as $sourceDeductionRequest) {
            $movementsDataRequest = $this->getMovementDataFromSourceDeductionRequest->execute(
                $sourceDeductionRequest,
                $metaData,
                DeductSourceItemQuantityOnRefundObserver::MOVEMENT_REFUND_TYPE
            );
            //phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $movementsData = array_merge($movementsData, $movementsDataRequest);
        }
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);
        if (count($movementsData)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }

        // Core function
        $this->processRefundItems->execute($order, $itemsToRefund, $returnToStockItems);

        $metaData = [
            InventoryMovementInterface::ACTION_LABEL => __('New Refund No'),
            InventoryMovementInterface::OBJECT_TYPE => 'refund',
            InventoryMovementInterface::OBJECT_ID => $creditmemo->getId(),
            InventoryMovementInterface::OBJECT_INCREMENT_ID => $creditmemo->getIncrementId()
        ];

        $movementsData = $this->getMovementDataFromSourceDeductionRequest->executeAfter(
            $movementsData,
            $metaData
        );
        /* Save log after processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);
    }

    /**
     * Is Valid Item
     *
     * @param string $sku
     * @param string|null $typeId
     * @return bool
     */
    private function isValidItem(string $sku, ?string $typeId): bool
    {
        //TODO: https://github.com/magento-engcom/msi/issues/1761
        // If product type located in table sales_order_item is "grouped" replace it with "simple"
        if ($typeId === 'grouped') {
            $typeId = 'simple';
        }

        $productType = $typeId ?: $this->getProductTypesBySkus->execute(
            [$sku]
        )[$sku];

        return $this->isSourceItemManagementAllowedForProductType->execute($productType);
    }
}
