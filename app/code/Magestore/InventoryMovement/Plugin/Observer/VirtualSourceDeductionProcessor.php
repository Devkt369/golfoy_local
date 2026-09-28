<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\Observer;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Event\Observer;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventoryShipping\Model\GetSourceSelectionResultFromInvoice;
use Magento\InventoryShipping\Model\SourceDeductionRequestsFromSourceSelectionFactory;
use Magento\InventoryShipping\Observer\VirtualSourceDeductionProcessor as CoreVirtualSourceDeductionProcessor;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Invoice;
use Magestore\InventoryMovement\Model\GetMovementDataFromSourceDeductionRequest;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;

/**
 * Class VirtualSourceDeductionProcessor
 *
 * Process events and dispatch event to save movement
 */
class VirtualSourceDeductionProcessor
{
    const MOVEMENT_INVOICE_TYPE = 'invoice_created';

    /**
     * @var GetSourceSelectionResultFromInvoice
     */
    private $getSourceSelectionResultFromInvoice;

    /**
     * @var SourceDeductionRequestsFromSourceSelectionFactory
     */
    private $sourceDeductionRequestsFromSourceSelectionFactory;

    /**
     * @var SalesEventInterfaceFactory
     */
    private $salesEventFactory;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var GetMovementDataFromSourceDeductionRequest
     */
    private $getMovementDataFromSourceDeductionRequest;

    /**
     * VirtualSourceDeductionProcessor constructor.
     *
     * @param GetSourceSelectionResultFromInvoice $getSourceSelectionResultFromInvoice
     * @param SourceDeductionRequestsFromSourceSelectionFactory $sourceDeductionRequestsFromSourceSelectionFactory
     * @param SalesEventInterfaceFactory $salesEventFactory
     * @param EventManager $eventManager
     * @param GetMovementDataFromSourceDeductionRequest $getMovementDataFromSourceDeductionRequest
     */
    public function __construct(
        GetSourceSelectionResultFromInvoice $getSourceSelectionResultFromInvoice,
        SourceDeductionRequestsFromSourceSelectionFactory $sourceDeductionRequestsFromSourceSelectionFactory,
        SalesEventInterfaceFactory $salesEventFactory,
        EventManager $eventManager,
        GetMovementDataFromSourceDeductionRequest $getMovementDataFromSourceDeductionRequest
    ) {
        $this->getSourceSelectionResultFromInvoice = $getSourceSelectionResultFromInvoice;
        $this->sourceDeductionRequestsFromSourceSelectionFactory = $sourceDeductionRequestsFromSourceSelectionFactory;
        $this->salesEventFactory = $salesEventFactory;
        $this->eventManager = $eventManager;
        $this->getMovementDataFromSourceDeductionRequest = $getMovementDataFromSourceDeductionRequest;
    }

    /**
     * Around deduct qty
     *
     * @param CoreVirtualSourceDeductionProcessor $subject
     * @param callable $proceed
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        CoreVirtualSourceDeductionProcessor $subject,
        callable $proceed,
        Observer $observer
    ) {
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        if (!$this->isValid($invoice)) {
            return $proceed($observer);
        }

        $sourceSelectionResult = $this->getSourceSelectionResultFromInvoice->execute($invoice);

        /** @var SalesEventInterface $salesEvent */
        $salesEvent = $this->salesEventFactory->create(
            [
                'type' => SalesEventInterface::EVENT_INVOICE_CREATED,
                'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                'objectId' => $invoice->getOrderId(),
            ]
        );

        $sourceDeductionRequests = $this->sourceDeductionRequestsFromSourceSelectionFactory->create(
            $sourceSelectionResult,
            $salesEvent,
            (int)$invoice->getOrder()->getStore()->getWebsiteId()
        );

        $metaData = [
            InventoryMovementInterface::ACTION_LABEL => __('New Invoice For Order No'),
            InventoryMovementInterface::OBJECT_TYPE => 'order',
            InventoryMovementInterface::OBJECT_ID => $invoice->getOrderId(),
            InventoryMovementInterface::OBJECT_INCREMENT_ID => $invoice->getOrder()->getIncrementId()
        ];
        $movementsData = [];
        foreach ($sourceDeductionRequests as $sourceDeductionRequest) {
            $movementsDataRequest = $this->getMovementDataFromSourceDeductionRequest->execute(
                $sourceDeductionRequest,
                $metaData,
                self::MOVEMENT_INVOICE_TYPE
            );
            //phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $movementsData = array_merge($movementsData, $movementsDataRequest);
        }
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);
        if (count($movementsData)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }

        /* Process the function */
        $result = $proceed($observer);

        $metaData = [
            InventoryMovementInterface::ACTION_LABEL => __('New Invoice No'),
            InventoryMovementInterface::OBJECT_TYPE => 'invoice',
            InventoryMovementInterface::OBJECT_ID => $invoice->getId(),
            InventoryMovementInterface::OBJECT_INCREMENT_ID => $invoice->getIncrementId()
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
     * Is Valid
     *
     * @param InvoiceInterface $invoice
     * @return bool
     */
    private function isValid(InvoiceInterface $invoice): bool
    {
        if ($invoice->getOrigData('entity_id')) {
            return false;
        }

        return $this->hasValidItems($invoice);
    }

    /**
     * Has Valid Items
     *
     * @param InvoiceInterface $invoice
     * @return bool
     */
    private function hasValidItems(InvoiceInterface $invoice): bool
    {
        foreach ($invoice->getItems() as $invoiceItem) {
            /** @var OrderItemInterface $orderItem */
            $orderItem = $invoiceItem->getOrderItem();
            if ($orderItem->getIsVirtual()) {
                return true;
            }
        }

        return false;
    }
}
