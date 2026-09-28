<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Quote;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\ToOrder;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magestore\ClickAndCollectApi\Api\Data\LocationAdditionalDataInterface;
use Magestore\ClickAndCollect\Model\Carrier\Method;

/**
 * Set Pickup information to the Order from Quote Address.
 *
 * The Pickup information will be pass to the Order only if selected delivery method is In-Store Pickup.
 */
class SetPickupInformationToOrder
{
    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * SetPickupInformationToOrder constructor.
     *
     * @param LocationRepositoryInterface $locationRepository
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        LocationRepositoryInterface $locationRepository,
        TimezoneInterface $timezone
    ) {
        $this->locationRepository = $locationRepository;
        $this->timezone = $timezone;
    }
    /**
     * Add Pickup Location code to the Order from Quote Address.
     *
     * @param ToOrder $subject
     * @param Address $address
     * @param array $data
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeConvert(ToOrder $subject, Address $address, array $data = []): array
    {
        if ($address->getShippingMethod() !== Method::CLICK_AND_COLLECT_SHIPPING_METHOD) {
            return [$address, $data];
        }

        $extension = $address->getExtensionAttributes();

        if ($extension && $extension->getLocationPickupId()) {
            $data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]
                [LocationAdditionalDataInterface::LOCATION_PICKUP_ID] = $extension->getLocationPickupId();
        }

        if ($extension && $extension->getPickupDateTime()) {
            $data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]
                [LocationAdditionalDataInterface::PICKUP_DATE_TIME] = $this->convertLocationTimeZoneToGMT(
                    $extension->getPickupDateTime(),
                    (int) $extension->getLocationPickupId()
                );
            /* Save pickup date in location's timezone for filter */
            $date = new \DateTime($extension->getPickupDateTime());
            $data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][LocationAdditionalDataInterface::PICKUP_DATE_ONLY]
                = $date->format('Y-m-d');
        }

        /* Only Date => No convert Timezone */
        if ($extension && $extension->getPickupDateOnly()) {
            $data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]
            [LocationAdditionalDataInterface::PICKUP_DATE_ONLY] = $extension->getPickupDateOnly();
        }

        return [$address, $data];
    }

    /**
     * Convert time of store to gmt to save in database
     *
     * @param string $pickupTime
     * @param int $locationId
     */
    public function convertLocationTimeZoneToGMT(string $pickupTime, int $locationId)
    {
        $date = new \DateTime($pickupTime, new \DateTimeZone($this->getTimezoneForLocationId($locationId)));
        $date->setTimezone(new \DateTimeZone('UTC'));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Get timezone by location id
     *
     * @param int $locationId
     * @return string
     */
    private function getTimezoneForLocationId(int $locationId)
    {
        $location = $this->locationRepository->getById($locationId);
        if ($location) {
            $storeId = $location->getStoreId();
            if ($storeId) {
                return $this->timezone->getConfigTimezone(
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            }
        }
        return $this->timezone->getConfigTimezone();
    }
}
