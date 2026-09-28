<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magestore\ClickAndCollect\Model\DistanceProvider\GetDistanceFromLocationToAddress;
use Magestore\ClickAndCollectApi\Api\DataProvider\GetStockIdsLinkedToSameSourcesWithStockInterface;
use Magestore\ClickAndCollectApi\Api\SortLocationsAlgorithmByDistanceInterface;
use Magestore\Webpos\Api\GetLatLngFromAddressInterface;
use Magestore\ClickAndCollectApi\Api\GetLocationsOrderedByDistanceInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * This algorithm just iterates over all the sources one by one in distance order in one stock
 */
class GetLocationsOrderedByDistance implements GetLocationsOrderedByDistanceInterface
{
    /**
     * @var GetDistanceFromLocationToAddress
     */
    private $getDistanceFromLocationToAddress;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetLatLngFromAddressInterface
     */
    private $getLatLngFromAddress;

    /**
     * @var GetStockIdsLinkedToSameSourcesWithStockInterface
     */
    private $getStockIdsLinkedToSameSourcesWithStock;

    /**
     * @var SortLocationsAlgorithmByDistanceInterface
     */
    private $sortLocationsAlgorithmByDistance;

    /**
     * GetLocationsOrderedByDistance constructor.
     * @param GetDistanceFromLocationToAddress $getDistanceFromLocationToAddress
     * @param LocationRepositoryInterface $locationRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param LoggerInterface $logger
     * @param GetStockIdsLinkedToSameSourcesWithStockInterface $getStockIdsLinkedToSameSourcesWithStock
     * @param SortLocationsAlgorithmByDistanceInterface $sortLocationsAlgorithmByDistance
     */
    public function __construct(
        GetDistanceFromLocationToAddress $getDistanceFromLocationToAddress,
        LocationRepositoryInterface $locationRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        LoggerInterface $logger,
        GetStockIdsLinkedToSameSourcesWithStockInterface $getStockIdsLinkedToSameSourcesWithStock,
        SortLocationsAlgorithmByDistanceInterface $sortLocationsAlgorithmByDistance
    ) {
        $this->getDistanceFromLocationToAddress = $getDistanceFromLocationToAddress;
        $this->locationRepository = $locationRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->logger = $logger;
        $this->getStockIdsLinkedToSameSourcesWithStock = $getStockIdsLinkedToSameSourcesWithStock;
        $this->sortLocationsAlgorithmByDistance = $sortLocationsAlgorithmByDistance;
    }

    /**
     * @inheritdoc
     */
    public function execute(
        string $address,
        float $radius,
        int $stockId = 0
    ): array {
        if ($stockId) {
            $stockIds = $this->getStockIdsLinkedToSameSourcesWithStock->execute($stockId);
            if (count($stockIds)) {
                $this->searchCriteriaBuilder->addFilter(LocationInterface::STOCK_ID, $stockIds, 'in');
            }
        }
        $this->searchCriteriaBuilder->addFilter('is_allow_pickup', 1);
        $locations = $this->locationRepository->getList($this->searchCriteriaBuilder->create());
        $locationCollection = $locations->getItems();
        $currentLatLngAddress = $this->getLatLngFromAddress->execute($address);

        return [
            'sort_location' =>
                $this->sortLocationsAlgorithmByDistance->execute($currentLatLngAddress, $locationCollection, $radius),
            'address_lat' => $currentLatLngAddress->getLat(),
            'address_lng' => $currentLatLngAddress->getLng()
        ];
    }
}
