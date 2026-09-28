<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model\Migrate;

use Magento\Framework\Exception\NotFoundException;
use Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface;
use Magestore\ClickAndCollectMigrate\Api\LocationStoreMapRepositoryInterface;
use Magestore\ClickAndCollectMigrate\Api\Migrate\MigrateLocationServiceInterface;
use Magestore\Storepickup\Model\StoreFactory;
use Magestore\Storepickup\Model\Store;
use Magestore\Storepickup\Model\ResourceModel\Store as StoreResourceModel;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterfaceFactory;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;

/**
 * Service Migrate Location
 */
class MigrateLocationService implements MigrateLocationServiceInterface
{
    /**
     * @var LocationStoreMapRepositoryInterface
     */
    protected $locationStoreMapRepository;

    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @var StoreResourceModel
     */
    protected $storeResourceModel;

    /**
     * @var LocationInterfaceFactory
     */
    protected $locationInterfaceFactory;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * MigrateLocationService constructor.
     * @param LocationStoreMapRepositoryInterface $locationStoreMapRepository
     * @param StoreFactory $storeFactory
     * @param StoreResourceModel $storeResourceModel
     * @param LocationInterfaceFactory $locationInterfaceFactory
     * @param LocationRepositoryInterface $locationRepository
     */
    public function __construct(
        LocationStoreMapRepositoryInterface $locationStoreMapRepository,
        StoreFactory $storeFactory,
        StoreResourceModel $storeResourceModel,
        LocationInterfaceFactory $locationInterfaceFactory,
        LocationRepositoryInterface $locationRepository
    ) {
        $this->locationStoreMapRepository = $locationStoreMapRepository;
        $this->storeFactory = $storeFactory;
        $this->storeResourceModel = $storeResourceModel;
        $this->locationInterfaceFactory = $locationInterfaceFactory;
        $this->locationRepository = $locationRepository;
    }

    /**
     * @inheritDoc
     */
    public function migrateLocation()
    {
        $locationStoreMapData = $this->locationStoreMapRepository->getList();
        foreach ($locationStoreMapData->getItems() as $locationStoreMap) {
            if (!$locationStoreMap->getLocationId()) {
                $this->createLocation($locationStoreMap);
            } else {
                $this->updateLocation($locationStoreMap);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function createLocation(LocationStoreMapInterface $locationStoreMap)
    {
        /** @var Store $store */
        $store = $this->storeFactory->create();
        $this->storeResourceModel->load($store, $locationStoreMap->getStorepickupId());
        if (!$store->getId()) {
            throw new NotFoundException(
                __(
                    'Store with id "%value" does not exist.',
                    ['value' => $locationStoreMap->getStorepickupId()]
                )
            );
        }
        /** @var LocationInterface $location */
        $location = $this->locationInterfaceFactory->create();
        $location->setName($store->getStoreName());
        $location->setDescription($store->getDescription());
        $location->setTelephone($store->getPhone());
        $location->setEmail($store->getEmail());
        $location->setStreet($store->getAddress());
        $location->setCity($store->getCity());
        $location->setRegion($store->getState());
        $location->setRegionId($store->getStateId());
        $location->setCountryId($store->getCountryId());
        $location->setCountry($store->getCountryName());
        $location->setPostcode($store->getZipcode());
        $location->setStockId($locationStoreMap->getStockId());
        $location->setLatitude($store->getLatitude());
        $location->setLongitude($store->getLongitude());
        $isAllowPickup = $store->isEnabled() ? 1 : 0;
        $location->setIsAllowPickup($isAllowPickup);
        $location->setScheduleId($store->getScheduleId());
        $this->locationRepository->save($location);

        // Update new location id to map data
        $locationStoreMap->setLocationId((int) $location->getLocationId());
        $this->locationStoreMapRepository->save($locationStoreMap);
    }

    /**
     * @inheritDoc
     */
    public function updateLocation(LocationStoreMapInterface $locationStoreMap)
    {
        /** @var Store $store */
        $store = $this->storeFactory->create();
        $this->storeResourceModel->load($store, $locationStoreMap->getStorepickupId());
        if (!$store->getId()) {
            throw new NotFoundException(
                __(
                    'Store with id "%value" does not exist.',
                    ['value' => $locationStoreMap->getStorepickupId()]
                )
            );
        }

        $location = $this->locationRepository->getById($locationStoreMap->getLocationId());
        $isAllowPickup = $store->isEnabled() ? 1 : 0;
        $location->setIsAllowPickup($isAllowPickup);
        $location->setScheduleId($store->getScheduleId());
        $this->locationRepository->save($location);
    }
}
