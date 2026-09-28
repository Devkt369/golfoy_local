<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

use Magento\Framework\Model\AbstractModel;
use Magestore\InventoryMovementApi\Api\Data\OrderPotentialErrorExtensionInterface;
use Magestore\InventoryMovement\Model\ResourceModel\OrderPotentialError as OrderPotentialErrorResource;
use Magestore\InventoryMovementApi\Api\Data\OrderPotentialErrorInterface;

/**
 * Class OrderPotentialError
 *
 * Used for Order Potential Error model
 */
class OrderPotentialError extends AbstractModel implements OrderPotentialErrorInterface
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(OrderPotentialErrorResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId(): ?int
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId(?int $orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderIncrementId(): ?string
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderIncrementId(?string $orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(?int $storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?string $status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getState(): ?string
    {
        return $this->getData(self::STATE);
    }

    /**
     * @inheritdoc
     */
    public function setState(?string $state)
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * @inheritdoc
     */
    public function getType(): ?string
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setType(?string $type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getBaseCurrencyCode(): ?string
    {
        return $this->getData(self::BASE_CURRENCY_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setBaseCurrencyCode(?string $baseCurrencyCode)
    {
        return $this->setData(self::BASE_CURRENCY_CODE, $baseCurrencyCode);
    }

    /**
     * @inheritdoc
     */
    public function getGrandTotal(): ?float
    {
        return $this->getData(self::GRAND_TOTAL);
    }

    /**
     * @inheritdoc
     */
    public function setGrandTotal(?float $grandTotal)
    {
        return $this->setData(self::GRAND_TOTAL, round((float)$grandTotal, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalPaidAmount(): ?float
    {
        return $this->getData(self::TOTAL_PAID_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setTotalPaidAmount(?float $totalPaidAmount)
    {
        return $this->setData(self::TOTAL_PAID_AMOUNT, round((float)$totalPaidAmount, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalRefundedAmount(): ?float
    {
        return $this->getData(self::TOTAL_REFUNDED_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setTotalRefundedAmount(?float $totalRefundedAmount)
    {
        return $this->setData(self::TOTAL_REFUNDED_AMOUNT, round((float)$totalRefundedAmount, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalQtyOrdered(): ?float
    {
        return $this->getData(self::TOTAL_QTY_ORDERED);
    }

    /**
     * @inheritdoc
     */
    public function setTotalQtyOrdered(?float $totalQtyOrdered)
    {
        return $this->setData(self::TOTAL_QTY_ORDERED, round((float)$totalQtyOrdered, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalVirtualQty(): ?float
    {
        return $this->getData(self::TOTAL_VIRTUAL_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setTotalVirtualQty(?float $totalVirtualQty)
    {
        return $this->setData(self::TOTAL_VIRTUAL_QTY, round((float)$totalVirtualQty, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalQtyShipped(): ?float
    {
        return $this->getData(self::TOTAL_QTY_SHIPPED);
    }

    /**
     * @inheritdoc
     */
    public function setTotalQtyShipped(?float $totalQtyShipped)
    {
        return $this->setData(self::TOTAL_QTY_SHIPPED, round((float)$totalQtyShipped, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalQtyShippedActual(): ?float
    {
        return $this->getData(self::TOTAL_QTY_SHIPPED_ACTUAL);
    }

    /**
     * @inheritdoc
     */
    public function setTotalQtyShippedActual(?float $totalQtyShippedActual)
    {
        return $this->setData(self::TOTAL_QTY_SHIPPED_ACTUAL, round((float)$totalQtyShippedActual, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalQtyInvoiced(): ?float
    {
        return $this->getData(self::TOTAL_QTY_INVOICED);
    }

    /**
     * @inheritdoc
     */
    public function setTotalQtyInvoiced(?float $totalQtyInvoiced)
    {
        return $this->setData(self::TOTAL_QTY_INVOICED, round((float)$totalQtyInvoiced, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalQtyInvoicedActual(): ?float
    {
        return $this->getData(self::TOTAL_QTY_INVOICED_ACTUAL);
    }

    /**
     * @inheritdoc
     */
    public function setTotalQtyInvoicedActual(?float $totalQtyInvoicedActual)
    {
        return $this->setData(self::TOTAL_QTY_INVOICED_ACTUAL, round((float)$totalQtyInvoicedActual, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalQtyRefunded(): ?float
    {
        return $this->getData(self::TOTAL_QTY_REFUNDED);
    }

    /**
     * @inheritdoc
     */
    public function setTotalQtyRefunded(?float $totalQtyRefunded)
    {
        return $this->setData(self::TOTAL_QTY_REFUNDED, round((float)$totalQtyRefunded, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalQtyRefundedActual(): ?float
    {
        return $this->getData(self::TOTAL_QTY_REFUNDED_ACTUAL);
    }

    /**
     * @inheritdoc
     */
    public function setTotalQtyRefundedActual(?float $totalQtyRefundedActual)
    {
        return $this->setData(self::TOTAL_QTY_REFUNDED_ACTUAL, round((float)$totalQtyRefundedActual, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalQtyCanceled(): ?float
    {
        return $this->getData(self::TOTAL_QTY_CANCELED);
    }

    /**
     * @inheritdoc
     */
    public function setTotalQtyCanceled(?float $totalQtyCanceled)
    {
        return $this->setData(self::TOTAL_QTY_CANCELED, round((float)$totalQtyCanceled, 4));
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?OrderPotentialErrorExtensionInterface
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(OrderPotentialErrorExtensionInterface $extensionAttributes): void
    {
        $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }
}
