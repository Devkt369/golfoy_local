<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Location;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkInterface;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;
use Magestore\ClickAndCollectApi\Api\GetLocationSpecialDayLinksInterface;
use Magestore\ClickAndCollectApi\Api\GetLocationSpecialDaysInterface;
use Magestore\ClickAndCollectApi\Api\SpecialDayRepositoryInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Psr\Log\LoggerInterface;

/**
 * Get location's special days
 */
class GetLocationSpecialDays implements GetLocationSpecialDaysInterface
{
    /**
     * @var GetLocationSpecialDayLinksInterface
     */
    protected $getLocationSpecialDayLinks;

    /**
     * @var SpecialDayRepositoryInterface
     */
    protected $specialDayRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * GetLocationSpecialDays constructor.
     * @param GetLocationSpecialDayLinksInterface $getLocationSpecialDayLinks
     * @param SpecialDayRepositoryInterface $specialDayRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetLocationSpecialDayLinksInterface $getLocationSpecialDayLinks,
        SpecialDayRepositoryInterface $specialDayRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LoggerInterface $logger
    ) {
        $this->getLocationSpecialDayLinks = $getLocationSpecialDayLinks;
        $this->specialDayRepository = $specialDayRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(LocationInterface $location): array
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilderFactory
                ->create()
                ->addFilter(LocationSpecialDayLinkInterface::LOCATION_ID, $location->getLocationId())
                ->create();
            $links = $this->getLocationSpecialDayLinks->execute($searchCriteria);
            $linkedSpecialDays = [];
            foreach ($links->getItems() as $item) {
                $linkedSpecialDays[] = $item->getSpecialDayId();
            }

            $searchCriteria = $this->searchCriteriaBuilderFactory
                ->create()
                ->addFilter(SpecialDayInterface::SPECIAL_DAY_ID, $linkedSpecialDays, 'in')
                ->create();
            $searchResult = $this->specialDayRepository->getList($searchCriteria);
            return $searchResult->getItems();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [];
        }
    }
}
