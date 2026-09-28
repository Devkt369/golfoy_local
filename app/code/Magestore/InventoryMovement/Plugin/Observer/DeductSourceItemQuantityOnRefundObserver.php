<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\Observer;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Event\Observer;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\ReturnProcessor\GetSourceDeductionRequestFromSourceSelection;
use Magento\InventorySales\Model\ReturnProcessor\GetSourceSelectionResultFromCreditMemoItems;
use Magento\InventorySales\Observer\SalesInventory\DeductSourceItemQuantityOnRefundObserver
    as CoreDeductSourceItemQuantityOnRefundObserver;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterfaceFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface as CreditmemoItem;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magestore\InventoryMovement\Model\GetMovementDataFromSourceDeductionRequest;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;

/**
 * Class DeductSourceItemQuantityOnRefundObserver
 *
 * Process events and dispatch event to save movement
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeductSourceItemQuantityOnRefundObserver
{
    const MOVEMENT_REFUND_TYPE = 'refund_created';

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
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var GetMovementDataFromSourceDeductionRequest
     */
    private $getMovementDataFromSourceDeductionRequest;

    /**
     * DeductSourceItemQuantityOnRefundObserver constructor.
     *
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param ItemsToRefundInterfaceFactory $itemsToRefundFactory
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param OrderRepositoryInterface $orderRepository
     * @param EventManager $eventManager
     * @param GetMovementDataFromSourceDeductionRequest $getMovementDataFromSourceDeductionRequest
     */
    public function __construct(
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        ItemsToRefundInterfaceFactory $itemsToRefundFactory,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        OrderRepositoryInterface $orderRepository,
        EventManager $eventManager,
        GetMovementDataFromSourceDeductionRequest $getMovementDataFromSourceDeductionRequest
    ) {
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemsToRefundFactory = $itemsToRefundFactory;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->getMovementDataFromSourceDeductionRequest = $getMovementDataFromSourceDeductionRequest;
    }

    /**
     * Around execute function
     *
     * @param CoreDeductSourceItemQuantityOnRefundObserver $subject
     * @param callable $proceed
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        CoreDeductSourceItemQuantityOnRefundObserver $subject,
        callable $proceed,
        Observer $observer
    ) {
        /* @var $creditmemo Creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $this->orderRepository->get($creditmemo->getOrderId());
        $itemsToRefund = $refundedOrderItemIds = [];
        /** @var CreditmemoItem $item */
        foreach ($creditmemo->getItems() as $item) {
            /** @var OrderItemInterface $orderItem */
            $orderItem = $item->getOrderItem();
            $sku = $this->getSkuFromOrderItem->execute($orderItem);

            if ($this->isValidItem($sku, $item)) {
                $refundedOrderItemIds[] = $item->getOrderItemId();
                $qty = (float)$item->getQty();
                $processedQty = $orderItem->getQtyInvoiced() - $orderItem->getQtyRefunded() + $qty;
                $itemsToRefund[$sku] = [
                    'qty' => ($itemsToRefund[$sku]['qty'] ?? 0) + $qty,
                    'processedQty' => ($itemsToRefund[$sku]['processedQty'] ?? 0) + (float)$processedQty
                ];
            }
        }

        $itemsToDeductFromSource = [];
        foreach ($itemsToRefund as $sku => $data) {
            $itemsToDeductFromSource[] = $this->itemsToRefundFactory->create(
                [
                    'sku' => $sku,
                    'qty' => $data['qty'],
                    'processedQty' => $data['processedQty']
                ]
            );
        }

        $movementsData = [];
        if (!empty($itemsToDeductFromSource)) {
            $sourceDeductionRequests = $this->getSourceDeductionRequestFromRefund(
                $order,
                $itemsToDeductFromSource,
                $refundedOrderItemIds
            );

            $metaData = [
                InventoryMovementInterface::ACTION_LABEL => __('New Refund For Order No'),
                InventoryMovementInterface::OBJECT_TYPE => 'order',
                InventoryMovementInterface::OBJECT_ID => $creditmemo->getOrderId(),
                InventoryMovementInterface::OBJECT_INCREMENT_ID => $creditmemo->getOrder()->getIncrementId()
            ];
            foreach ($sourceDeductionRequests as $sourceDeductionRequest) {
                $movementsDataRequest = $this->getMovementDataFromSourceDeductionRequest->execute(
                    $sourceDeductionRequest,
                    $metaData,
                    self::MOVEMENT_REFUND_TYPE
                );
                //phpcs:ignore Magento2.Performance.ForeachArrayMerge
                $movementsData = array_merge($movementsData, $movementsDataRequest);
            }
        }
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);
        if (count($movementsData)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }

        /* Process the function */
        $result = $proceed($observer);

        $metaData = [
            InventoryMovementInterface::ACTION_LABEL => __('New Refund No'),
            InventoryMovementInterface::OBJECT_TYPE => 'refund',
            InventoryMovementInterface::OBJECT_ID => $creditmemo->getId(),
            InventoryMovementInterface::OBJECT_INCREMENT_ID => $order->getIncrementId()
        ];
        $movementsData = $this->getMovementDataFromSourceDeductionRequest->executeAfter(
            $movementsData,
            $metaData
        );
        /* Save log after processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);

        return $result;
    }

    /**
     * Get Source Deduction Request From Refund
     *
     * @param OrderInterface $order
     * @param array $itemsToRefund
     * @param array $itemsToDeductFromSource
     * @return array|SourceDeductionRequestInterface[]
     */
    public function getSourceDeductionRequestFromRefund(
        OrderInterface $order,
        array $itemsToRefund,
        array $itemsToDeductFromSource
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var GetSourceSelectionResultFromCreditMemoItems $getSourceSelectionResultFromCreditMemoItems */
        $getSourceSelectionResultFromCreditMemoItems = $objectManager->get(
            GetSourceSelectionResultFromCreditMemoItems::class
        );
        $sourceSelectionResult = $getSourceSelectionResultFromCreditMemoItems->execute(
            $order,
            $itemsToRefund,
            $itemsToDeductFromSource
        );

        /** @var GetSourceDeductionRequestFromSourceSelection $getSourceDeductionRequestFromSourceSelection */
        $getSourceDeductionRequestFromSourceSelection = $objectManager->get(
            GetSourceDeductionRequestFromSourceSelection::class
        );
        return $getSourceDeductionRequestFromSourceSelection->execute(
            $order,
            $sourceSelectionResult
        );
    }

    /**
     * Is item valid to change qty
     *
     * @param string $sku
     * @param CreditmemoItem $item
     * @return bool
     */
    private function isValidItem(string $sku, CreditmemoItem $item): bool
    {
        /** @var OrderItemInterface $orderItem */
        $orderItem = $item->getOrderItem();
        // Since simple products which are the part of a grouped product are saved in the database
        // (table sales_order_item) with product type grouped, we manually change the type of
        // product from grouped to simple which support source management.
        $typeId = $orderItem->getProductType() === 'grouped' ? 'simple' : $orderItem->getProductType();

        $productType = $typeId ?: $this->getProductTypesBySkus->execute(
            [$sku]
        )[$sku];

        return $this->isSourceItemManagementAllowedForProductType->execute($productType)
            && $item->getQty() > 0
            && !$item->getBackToStock();
    }
}
