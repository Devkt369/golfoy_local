<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\DistanceProvider;

use Magestore\ClickAndCollectApi\Api\GetDistanceInterface;
use Magestore\Webpos\Api\GetLatLngFromAddressInterface;
use Magestore\Webpos\Model\DistanceProvider\GetLatLngFromLocation;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;

/**
 * Class GetDistanceFromLocationToAddress
 *
 * Get distance from google api
 */
class GetDistanceFromLocationToAddress
{
    /**
     * @var GetLatLngFromLocation
     */
    private $getLatLngFromLocation;

    /**
     * @var GetLatLngFromAddressInterface
     */
    private $getLatLngFromAddress;

    /**
     * @var GetDistanceInterface
     */
    private $getDistance;

    /**
     * GetDistanceFromSourceToAddress constructor.
     *
     * @param GetLatLngFromLocation $getLatLngFromLocation
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param GetDistanceInterface $getDistance
     */
    public function __construct(
        GetLatLngFromLocation $getLatLngFromLocation,
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        GetDistanceInterface $getDistance
    ) {
        $this->getLatLngFromLocation = $getLatLngFromLocation;
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->getDistance = $getDistance;
    }

    /**
     * Get distance from source to address
     *
     * @param LocationInterface $location
     * @param LatLngInterface $currentLatLngAddress
     * @return float
     */
    public function execute(LocationInterface $location, LatLngInterface $currentLatLngAddress): float
    {
        return $this->getDistance->execute(
            $this->getLatLngFromLocation->execute($location),
            $currentLatLngAddress
        );
    }
}
