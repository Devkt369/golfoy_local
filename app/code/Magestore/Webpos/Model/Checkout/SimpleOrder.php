<?php

namespace Magestore\Webpos\Model\Checkout;

use Magento\Framework\Model\AbstractModel;
use Magestore\Webpos\Api\Data\Checkout\SimpleOrderExtensionInterface;
use Magestore\Webpos\Api\Data\Checkout\SimpleOrderInterface;

/**
 * Checkout - SimpleOrder
 */
class SimpleOrder extends AbstractModel implements SimpleOrderInterface
{

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getState()
    {
        return $this->getData(self::STATE);
    }

    /**
     * @inheritdoc
     */
    public function setState($state)
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getGrandTotal()
    {
        return $this->getData(self::GRAND_TOTAL);
    }

    /**
     * @inheritdoc
     */
    public function setGrandTotal($grandTotal)
    {
        return $this->setData(self::GRAND_TOTAL, round((float)$grandTotal, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalPaid()
    {
        return $this->getData(self::TOTAL_PAID);
    }

    /**
     * @inheritdoc
     */
    public function setTotalPaid($totalPaid)
    {
        return $this->setData(self::TOTAL_PAID, round((float)$totalPaid, 4));
    }

    /**
     * @inheritdoc
     */
    public function getTotalDue()
    {
        return $this->getData(self::TOTAL_DUE);
    }

    /**
     * @inheritdoc
     */
    public function setTotalDue($totalDue)
    {
        return $this->setData(self::TOTAL_DUE, round((float)$totalDue, 4));
    }

    /**
     * @inheritdoc
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
    /**
     * @inheritDoc
     */
    public function getExtensionAttributes()
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(
        SimpleOrderExtensionInterface $extensionAttributes
    ) {
        return $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }
}
