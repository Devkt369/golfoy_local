<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Model\DistanceProvider;

use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToString;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;
use Magestore\Webpos\Api\GetLatLngFromAddressInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Setup\Patch\Data\UpdateCoordinateWebposLocation;

/**
 * Class GetLatLngFromLocation
 *
 * Get position lat and lng
 */
class GetLatLngFromLocation
{
    /**
     * @var AddressInterfaceFactory
     */
    private $addressInterfaceFactory;

    /**
     * @var GetLatLngFromAddressInterface
     */
    private $getLatLngFromAddress;

    /**
     * @var LatLngInterfaceFactory
     */
    private $latLngInterfaceFactory;

    /**
     * @var AddressToString
     */
    private $addressToString;

    /**
     * GetLatLngFromLocation constructor.
     *
     * @param AddressInterfaceFactory $addressInterfaceFactory
     * @param LatLngInterfaceFactory $latLngInterfaceFactory
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param AddressToString $addressToString
     */
    public function __construct(
        AddressInterfaceFactory $addressInterfaceFactory,
        LatLngInterfaceFactory $latLngInterfaceFactory,
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        AddressToString $addressToString
    ) {
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->latLngInterfaceFactory = $latLngInterfaceFactory;
        $this->addressToString = $addressToString;
    }

    /**
     * Get latitude and longitude from source
     *
     * @param LocationInterface $location
     * @return LatLngInterface
     */
    public function execute(LocationInterface $location): LatLngInterface
    {

        if (UpdateCoordinateWebposLocation::DEFAULT_COORDINATES_VALUE == $location->getLatitude() ||
            UpdateCoordinateWebposLocation::DEFAULT_COORDINATES_VALUE == $location->getLongitude()
        ) {
            $locationAddress = $this->addressInterfaceFactory->create([
                'country' => $location->getCountryId() ?? '',
                'postcode' => $location->getPostcode() ?? '',
                'street' => $location->getStreet() ?? '',
                'region' => $location->getRegion() ?? '',
                'city' => $location->getCity() ?? ''
            ]);

            return $this->getLatLngFromAddress->execute($this->addressToString->execute($locationAddress));
        }

        return $this->latLngInterfaceFactory->create([
            'lat' => (float) $location->getLatitude(),
            'lng' => (float) $location->getLongitude()
        ]);
    }
}
