<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magestore\ClickAndCollect\Model\DistanceProvider\GetDistanceFromLocationToAddress;
use Magestore\ClickAndCollectApi\Api\SortLocationsAlgorithmByDistanceInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Psr\Log\LoggerInterface;

/**
 * This algorithm just iterates over all the sources one by one in distance order in one stock
 */
class SortLocationsAlgorithmByDistance implements SortLocationsAlgorithmByDistanceInterface
{
    /**
     * @var GetDistanceFromLocationToAddress
     */
    private $getDistanceFromLocationToAddress;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SortLocationsAlgorithmByDistance constructor.
     * @param GetDistanceFromLocationToAddress $getDistanceFromLocationToAddress
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetDistanceFromLocationToAddress $getDistanceFromLocationToAddress,
        LoggerInterface $logger
    ) {
        $this->getDistanceFromLocationToAddress = $getDistanceFromLocationToAddress;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(
        LatLngInterface $currentLatLngAddress,
        array $locationCollection,
        float $radius = null
    ): array {
        /* Similar Magento Distance Algorithm */
        /* This algorithm just iterates over all the location one by one in distance order */
        $distanceByLocationId = $sortLocations = [];
        foreach ($locationCollection as $location) {
            try {
                $distance = $this->getDistanceFromLocationToAddress->execute(
                    $location,
                    $currentLatLngAddress
                );

                if ($distance <= $radius || !$radius) {
                    $distanceByLocationId[$location->getLocationId()] = $distance;
                    $location->setDistance($distance);
                    $sortLocations[] = $location;
                }
            } catch (LocalizedException $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        // Sort locations by distance
        uasort(
            $sortLocations,
            function (LocationInterface $a, LocationInterface $b) use ($distanceByLocationId) {
                $distanceFromA = $distanceByLocationId[$a->getLocationId()];
                $distanceFromB = $distanceByLocationId[$b->getLocationId()];

                return ($distanceFromA < $distanceFromB) ? -1 : 1;
            }
        );

        return $sortLocations;
    }
}
