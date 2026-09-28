<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Quote\Address;

use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Api\Data\AddressExtensionInterfaceFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address;
use Magestore\ClickAndCollectApi\Api\Data\LocationAdditionalDataInterface;

/**
 * Load Pickup Location Code for Quote Address.
 */
class LoadPickupLocationForQuoteAddress
{
    /**
     * @var AddressExtensionInterfaceFactory
     */
    private $addressExtensionInterfaceFactory;

    /**
     * @param AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory
     */
    public function __construct(
        AddressExtensionInterfaceFactory $addressExtensionInterfaceFactory
    ) {
        $this->addressExtensionInterfaceFactory = $addressExtensionInterfaceFactory;
    }

    /**
     * Load and add Pickup Location information to Quote Address.
     *
     * @param Address $subject
     * @param Address $result
     * @param AbstractModel $entity
     *
     * @return Address
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoad(Address $subject, Address $result, AbstractModel $entity): Address
    {
        if (!$entity->getId()) {
            return $result;
        }

        if (!$entity->getExtensionAttributes()) {
            $entity->setExtensionAttributes($this->addressExtensionInterfaceFactory->create());
        }

        $entity->getExtensionAttributes()->setLocationPickupId(
            $entity->getData(LocationAdditionalDataInterface::LOCATION_PICKUP_ID)
        );
        $entity->getExtensionAttributes()->setPickupDateTime(
            $entity->getData(LocationAdditionalDataInterface::PICKUP_DATE_TIME)
        );
        $entity->getExtensionAttributes()->setPickupDateOnly(
            $entity->getData(LocationAdditionalDataInterface::PICKUP_DATE_ONLY)
        );

        return $result;
    }
}
