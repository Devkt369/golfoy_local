<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Cron;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\WebposPointy\Api\PointyIntegrationInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magestore\WebposPointy\Model\SubmitSales\Consumer;

/**
 * Cron to submit sales to pointy
 */
class SubmitSales
{
    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * SubmitSales constructor.
     * @param LocationRepositoryInterface $locationRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param PublisherInterface $publisher
     */
    public function __construct(
        LocationRepositoryInterface $locationRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        PublisherInterface $publisher
    ) {
        $this->locationRepository = $locationRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->publisher = $publisher;
    }

    /**
     * Process submit sales to pointy
     */
    public function execute()
    {
        $filterPointyEnable = $this->filterBuilder
            ->setField(PointyIntegrationInterface::POINTY_ENABLE)
            ->setConditionType('eq')
            ->setValue(PointyIntegrationInterface::VALUE_ENABLED)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filterPointyEnable])->create();
        $locations = $this->locationRepository->getList($searchCriteria);
        if (!count($locations->getItems())) {
            return;
        }
        foreach ($locations->getItems() as $location) {
            $this->publisher->publish(Consumer::QUEUE_TOPIC_NAME, (int)$location->getLocationId());
        }
    }
}
