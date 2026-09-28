<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Rewardpoints\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magestore\Rewardpoints\Api\Data\Integration\Webpos\RateInterface;
use Magestore\Rewardpoints\Api\GetRateByCustomerInterface;
use Magestore\Rewardpoints\Model\ResourceModel\Rate\Collection;
use Magestore\Rewardpoints\Model\ResourceModel\Rate\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Rewardpoints - GetRateByCustomer
 */
class GetRateByCustomer implements GetRateByCustomerInterface
{
    const FIND_IN_SET = 'finset';
    /**
     * @var CollectionFactory
     */
    protected $rateCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * PackageRepository constructor.
     *
     * @param CollectionFactory $rateCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     * @param LoggerInterface $logger
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        CollectionFactory $rateCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->rateCollectionFactory = $rateCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionProcessor = $collectionProcessor;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute($direction, $customerGroupId, $websiteId): array
    {
        /** @var Collection $collection */
        $collection = $this->rateCollectionFactory->create();

        $collection->addFieldToFilter(RateInterface::DIRECTION, $direction)
            ->addFieldToFilter(RateInterface::WEBSITE_IDS, [self::FIND_IN_SET => $websiteId])
            ->addFieldToFilter(RateInterface::CUSTOMER_GROUP_IDS, [self::FIND_IN_SET => $customerGroupId])
            ->addFieldToFilter(RateInterface::STATUS, ['eq' => Rate::STATUS_ACTIVE]);
        $collection->getSelect()->order('sort_order ASC');
        $collection->getSelect()->order('rate_id DESC');
        /** @var Rate $rate */
        $rate = $collection->getFirstItem();

        $rateArray = [];
        if ($rate && $rate->getId()) {
            $rateArray[] = $rate->getData();
        }

        return $rateArray;
    }
}
