<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Quote\Address;

use Magento\Quote\Model\Quote\Address;

/**
 * Save selected Pickup Location Code for Quote Address.
 */
class ManageAssignmentOfPickupLocationToQuoteAddress
{
    /**
     * Save information about associate Pickup Location to Quote Address.
     *
     * @param Address $subject
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(Address $subject): array
    {
        if (!$this->validateAddress($subject)) {
            return [];
        }

        $subject->setLocationPickupId($subject->getExtensionAttributes()->getLocationPickupId());
        $subject->setPickupDateTime($subject->getExtensionAttributes()->getPickupDateTime());
        $subject->setPickupDateOnly($subject->getExtensionAttributes()->getPickupDateOnly());

        return [];
    }

    /**
     * Check if address can have a Pickup Location.
     *
     * @param Address $address
     *
     * @return bool
     */
    private function validateAddress(Address $address): bool
    {
        return $address->getExtensionAttributes() && $address->getAddressType() === Address::ADDRESS_TYPE_SHIPPING;
    }
}
