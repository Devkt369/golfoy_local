<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents Order Potential Error
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface OrderPotentialErrorInterface extends ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ID = 'id';
    const ORDER_ID = 'order_id';
    const ORDER_INCREMENT_ID = 'order_increment_id';
    const CREATED_AT = 'created_at';
    const STORE_ID = 'store_id';
    const STATUS = 'status';
    const STATE = 'state';
    const TYPE = 'type';
    const GRAND_TOTAL = 'grand_total';
    const BASE_CURRENCY_CODE = 'base_currency_code';
    const TOTAL_PAID_AMOUNT = 'total_paid_amount';
    const TOTAL_REFUNDED_AMOUNT = 'total_refunded_amount';
    const TOTAL_QTY_ORDERED = 'total_qty_ordered';
    const TOTAL_VIRTUAL_QTY = 'total_virtual_qty';
    const TOTAL_QTY_SHIPPED = 'total_qty_shipped';
    const TOTAL_QTY_SHIPPED_ACTUAL = 'total_qty_shipped_actual';
    const TOTAL_QTY_INVOICED = 'total_qty_invoiced';
    const TOTAL_QTY_INVOICED_ACTUAL = 'total_qty_invoiced_actual';
    const TOTAL_QTY_REFUNDED = 'total_qty_refunded';
    const TOTAL_QTY_REFUNDED_ACTUAL = 'total_qty_refunded_actual';
    const TOTAL_QTY_CANCELED = 'total_qty_canceled';
    /**#@-*/

    /**
     * Constants for order type
     */
    const TYPE_MAGENTO_ORDER = 'magento_order';
    const TYPE_POS_UNSYNCED_ORDER = 'pos_unsynced_order';
    /**#@-*/

    /**
     * Get Id
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set Id
     *
     * @param int|null $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get Order Id
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * Set Order Id
     *
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId);

    /**
     * Get Order Increment Id
     *
     * @return string|null
     */
    public function getOrderIncrementId(): ?string;

    /**
     * Set Order Increment Id
     *
     * @param string|null $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId(?string $orderIncrementId);

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set Created At
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt);

    /**
     * Get Store Id
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set Store Id
     *
     * @param int|null $storeId
     * @return $this
     */
    public function setStoreId(?int $storeId);

    /**
     * Get Status
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set Status
     *
     * @param string|null $status
     * @return $this
     */
    public function setStatus(?string $status);

    /**
     * Get State
     *
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * Set State
     *
     * @param string|null $state
     * @return $this
     */
    public function setState(?string $state);

    /**
     * Get Type
     *
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * Set Type
     *
     * @param string|null $type
     * @return $this
     */
    public function setType(?string $type);

    /**
     * Get Base Currency Code
     *
     * @return string|null
     */
    public function getBaseCurrencyCode(): ?string;

    /**
     * Set Base Currency Code
     *
     * @param string|null $baseCurrencyCode
     * @return $this
     */
    public function setBaseCurrencyCode(?string $baseCurrencyCode);

    /**
     * Get Grand Total
     *
     * @return float|null
     */
    public function getGrandTotal(): ?float;

    /**
     * Set Grand Total
     *
     * @param float|null $grandTotal
     * @return $this
     */
    public function setGrandTotal(?float $grandTotal);

    /**
     * Get Total Paid Amount
     *
     * @return float|null
     */
    public function getTotalPaidAmount(): ?float;

    /**
     * Set Total Paid Amount
     *
     * @param float|null $totalPaidAmount
     * @return $this
     */
    public function setTotalPaidAmount(?float $totalPaidAmount);

    /**
     * Get Total Refunded Amount
     *
     * @return float|null
     */
    public function getTotalRefundedAmount(): ?float;

    /**
     * Set Total Refunded Amount
     *
     * @param float|null $totalRefundedAmount
     * @return $this
     */
    public function setTotalRefundedAmount(?float $totalRefundedAmount);

    /**
     * Get Total Qty Ordered
     *
     * @return float|null
     */
    public function getTotalQtyOrdered(): ?float;

    /**
     * Set Total Qty Ordered
     *
     * @param float|null $totalQtyOrdered
     * @return $this
     */
    public function setTotalQtyOrdered(?float $totalQtyOrdered);

    /**
     * Get Total Virtual Qty
     *
     * @return float|null
     */
    public function getTotalVirtualQty(): ?float;

    /**
     * Set Total Virtual Qty
     *
     * @param float|null $totalVirtualQty
     * @return $this
     */
    public function setTotalVirtualQty(?float $totalVirtualQty);

    /**
     * Get Total Qty Shipped
     *
     * @return float|null
     */
    public function getTotalQtyShipped(): ?float;

    /**
     * Set Total Qty Shipped
     *
     * @param float|null $totalQtyShipped
     * @return $this
     */
    public function setTotalQtyShipped(?float $totalQtyShipped);

    /**
     * Get Total Qty Shipped Actual
     *
     * @return float|null
     */
    public function getTotalQtyShippedActual(): ?float;

    /**
     * Set Total Qty Shipped Actual
     *
     * @param float|null $totalQtyShippedActual
     * @return $this
     */
    public function setTotalQtyShippedActual(?float $totalQtyShippedActual);

    /**
     * Get Total Qty Invoiced
     *
     * @return float|null
     */
    public function getTotalQtyInvoiced(): ?float;

    /**
     * Set Total Qty Invoiced
     *
     * @param float|null $totalQtyInvoiced
     * @return $this
     */
    public function setTotalQtyInvoiced(?float $totalQtyInvoiced);

    /**
     * Get Total Qty Invoiced Actual
     *
     * @return float|null
     */
    public function getTotalQtyInvoicedActual(): ?float;

    /**
     * Set Total Qty Invoiced Actual
     *
     * @param float|null $totalQtyInvoicedActual
     * @return $this
     */
    public function setTotalQtyInvoicedActual(?float $totalQtyInvoicedActual);

    /**
     * Get Total Qty Refunded
     *
     * @return float|null
     */
    public function getTotalQtyRefunded(): ?float;

    /**
     * Set Total Qty Refunded
     *
     * @param float|null $totalQtyRefunded
     * @return $this
     */
    public function setTotalQtyRefunded(?float $totalQtyRefunded);

    /**
     * Get Total Qty Refunded Actual
     *
     * @return float|null
     */
    public function getTotalQtyRefundedActual(): ?float;

    /**
     * Set Total Qty Refunded Actual
     *
     * @param float|null $totalQtyRefundedActual
     * @return $this
     */
    public function setTotalQtyRefundedActual(?float $totalQtyRefundedActual);

    /**
     * Get Total Qty Canceled
     *
     * @return float|null
     */
    public function getTotalQtyCanceled(): ?float;

    /**
     * Set Total Qty Canceled
     *
     * @param float|null $totalQtyCanceled
     * @return $this
     */
    public function setTotalQtyCanceled(?float $totalQtyCanceled);

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\InventoryMovementApi\Api\Data\OrderPotentialErrorExtensionInterface|null
     */
    public function getExtensionAttributes()
        : ?\Magestore\InventoryMovementApi\Api\Data\OrderPotentialErrorExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\InventoryMovementApi\Api\Data\OrderPotentialErrorExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\InventoryMovementApi\Api\Data\OrderPotentialErrorExtensionInterface $extensionAttributes
    ): void;
}
