<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPointy\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;
use Magestore\WebposPointy\Api\PointyIntegrationInterface;
use Magestore\WebposPointy\Model\Inventory\Consumer;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

/**
 * Class SubmitInventory
 *
 * Used to submit inventory data to pointy
 */
class SubmitInventory
{
    /**
     * @var PublisherInterface
     */
    protected $messagePublisher;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * @var PointyConfigManagementInterface
     */
    protected $pointyConfigManagement;

    /**
     * SubmitInventory constructor.
     * @param PublisherInterface $messagePublisher
     * @param LocationRepositoryInterface $locationRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param PointyConfigManagementInterface $pointyConfigManagement
     */
    public function __construct(
        PublisherInterface $messagePublisher,
        LocationRepositoryInterface $locationRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        PointyConfigManagementInterface $pointyConfigManagement
    ) {
        $this->messagePublisher = $messagePublisher;
        $this->locationRepository = $locationRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->pointyConfigManagement = $pointyConfigManagement;
    }

    /**
     * Execute
     */
    public function execute()
    {
        if ($this->pointyConfigManagement->isPointyEnabled()
            && $this->pointyConfigManagement->getClientAuthKey()
        ) {
            /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteriaBuilder->addFilter(
                PointyIntegrationInterface::POINTY_ENABLE,
                PointyIntegrationInterface::VALUE_ENABLED
            );

            $searchResult = $this->locationRepository->getList($searchCriteriaBuilder->create());
            foreach ($searchResult->getItems() as $location) {
                $this->messagePublisher->publish(Consumer::QUEUE_TOPIC_NAME, (int)$location->getLocationId());
            }
        }
    }
}
