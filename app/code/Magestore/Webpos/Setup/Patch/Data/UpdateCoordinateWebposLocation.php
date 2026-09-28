<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Setup\Patch\Data;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magestore\Webpos\Model\DistanceProvider\GetLatLngFromLocation;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Schema patch to add column to location table
 */
class UpdateCoordinateWebposLocation implements DataPatchInterface
{
    const DEFAULT_COORDINATES_VALUE = 0;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var GetLatLngFromLocation
     */
    private $getLatLngFromLocation;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param LocationRepositoryInterface $locationRepository
     * @param GetLatLngFromLocation $getLatLngFromLocation
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LocationRepositoryInterface $locationRepository,
        GetLatLngFromLocation $getLatLngFromLocation,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->locationRepository = $locationRepository;
        $this->getLatLngFromLocation = $getLatLngFromLocation;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilderFactory->create()
                ->addFilter(LocationInterface::STOCK_ID, 0, 'gt')
                ->create();
            $locations = $this->locationRepository->getList($searchCriteria);
            foreach ($locations->getItems() as $item) {

                if (self::DEFAULT_COORDINATES_VALUE == $item->getLatitude() ||
                    self::DEFAULT_COORDINATES_VALUE == $item->getLongitude()
                ) {
                    $currentLatLngAddress = $this->getLatLngFromLocation->execute($item);
                    $item->setLatitude($currentLatLngAddress->getLat());
                    $item->setLongitude($currentLatLngAddress->getLng());
                    $this->locationRepository->save($item);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
