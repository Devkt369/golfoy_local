<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink as LocationSpecialDayLinkResourceModel;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkExtensionInterface;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkInterface;

/**
 * Class Schedule
 *
 * Used for schedule model
 */
class LocationSpecialDayLink extends AbstractExtensibleModel implements LocationSpecialDayLinkInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(LocationSpecialDayLinkResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getLinkId(): ?int
    {
        return (int) $this->getData(self::LINK_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLinkId(?int $linkId): void
    {
        $this->setData(self::LINK_ID, $linkId);
    }

    /**
     * @inheritdoc
     */
    public function getLocationId(): ?int
    {
        return (int) $this->getData(self::LOCATION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLocationId(?int $locationId): void
    {
        $this->setData(self::LOCATION_ID, $locationId);
    }

    /**
     * @inheritdoc
     */
    public function getSpecialDayId(): ?int
    {
        return (int) $this->getData(self::SPECIALDAY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setSpecialDayId(?int $specialDayId): void
    {
        $this->setData(self::SPECIALDAY_ID, $specialDayId);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?LocationSpecialDayLinkExtensionInterface
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(
                LocationSpecialDayLinkInterface::class
            );
            $this->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(LocationSpecialDayLinkExtensionInterface $extensionAttributes): void
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
