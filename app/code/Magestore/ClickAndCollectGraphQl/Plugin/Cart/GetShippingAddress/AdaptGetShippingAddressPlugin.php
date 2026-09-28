<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Plugin\Cart\GetShippingAddress;

use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Api\Data\AddressExtensionFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\QuoteGraphQl\Model\Cart\GetShippingAddress;

/**
 * Set shipping address to the cart. Proceed with passed Pickup information.
 */
class AdaptGetShippingAddressPlugin
{
    /**
     * @var AddressExtensionFactory
     */
    private $addressExtensionFactory;

    /**
     * @param AddressExtensionFactory $addressExtensionFactory
     */
    public function __construct(
        AddressExtensionFactory $addressExtensionFactory
    ) {
        $this->addressExtensionFactory = $addressExtensionFactory;
    }

    /**
     * Get shipping address for store pickup based on the input.
     *
     * @param GetShippingAddress $subject
     * @param Address $result
     * @param ContextInterface $context
     * @param array $shippingAddressInput
     * @return Address
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        GetShippingAddress $subject,
        Address $result,
        ContextInterface $context,
        array $shippingAddressInput
    ): Address {
        $this->assignPickupInformation($result, $shippingAddressInput);

        return $result;
    }

    /**
     * Set to Quote Address Pickup information, if it was provided.
     *
     * @param Address $address
     * @param array $shippingAddressInput
     */
    private function assignPickupInformation(Address $address, array $shippingAddressInput): void
    {
        $pickupLocationId = $shippingAddressInput['location_pickup_id'] ?? null;
        $pickupDateTime = $shippingAddressInput['pickup_date_time'] ?? null;
        $pickupDateOnly = $shippingAddressInput['pickup_date_only'] ?? null;

        $extension = $address->getExtensionAttributes();
        if (!$extension) {
            $extension = $this->addressExtensionFactory->create();
            $address->setExtensionAttributes($extension);
        }

        $extension->setLocationPickupId($pickupLocationId);
        $extension->setPickupDateTime($pickupDateTime);
        $extension->setPickupDateOnly($pickupDateOnly);
    }
}
