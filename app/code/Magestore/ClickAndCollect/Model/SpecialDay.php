<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magestore\ClickAndCollect\Model\ResourceModel\SpecialDay as SpecialDayResourceModel;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayExtensionInterface;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;

/**
 * Class Special Day
 *
 * Used for Special Day model
 */
class SpecialDay extends AbstractExtensibleModel implements SpecialDayInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(SpecialDayResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getSpecialDayId(): ?int
    {
        return (int) $this->getData(self::SPECIAL_DAY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setSpecialDayId(?int $specialDayId): void
    {
        $this->setData(self::SPECIAL_DAY_ID, $specialDayId);
    }

    /**
     * @inheritdoc
     */
    public function getSpecialDayName(): ?string
    {
        return (string) $this->getData(self::SPECIAL_DAY_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setSpecialDayName(?string $specialDayName): void
    {
        $this->setData(self::SPECIAL_DAY_NAME, $specialDayName);
    }

    /**
     * @inheritdoc
     */
    public function getStoreStatus(): ?int
    {
        return (int) $this->getData(self::STORE_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStoreStatus(?int $storeStatus): void
    {
        $this->setData(self::STORE_STATUS, $storeStatus);
    }

    /**
     * @inheritdoc
     */
    public function getRepeat(): ?int
    {
        return (int) $this->getData(self::REPEAT);
    }

    /**
     * @inheritdoc
     */
    public function setRepeat(?int $repeat): void
    {
        $this->setData(self::REPEAT, $repeat);
    }

    /**
     * @inheritdoc
     */
    public function getDateFrom(): ?string
    {
        return (string) $this->getData(self::DATE_FROM);
    }

    /**
     * @inheritdoc
     */
    public function setDateFrom(?string $dateFrom): void
    {
        $this->setData(self::DATE_FROM, $dateFrom);
    }

    /**
     * @inheritdoc
     */
    public function getDateTo(): ?string
    {
        return (string) $this->getData(self::DATE_TO);
    }

    /**
     * @inheritdoc
     */
    public function setDateTo(?string $dateTo): void
    {
        $this->setData(self::DATE_TO, $dateTo);
    }

    /**
     * @inheritdoc
     */
    public function getTimeOpen(): ?string
    {
        return (string) $this->getData(self::TIME_OPEN);
    }

    /**
     * @inheritdoc
     */
    public function setTimeOpen(?string $timeOpen): void
    {
        $this->setData(self::TIME_OPEN, $timeOpen);
    }

    /**
     * @inheritdoc
     */
    public function getTimeClose(): ?string
    {
        return (string) $this->getData(self::TIME_CLOSE);
    }

    /**
     * @inheritdoc
     */
    public function setTimeClose(?string $timeClose): void
    {
        $this->setData(self::TIME_CLOSE, $timeClose);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?SpecialDayExtensionInterface
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SpecialDayExtensionInterface::class);
            $this->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(SpecialDayExtensionInterface $extensionAttributes): void
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
