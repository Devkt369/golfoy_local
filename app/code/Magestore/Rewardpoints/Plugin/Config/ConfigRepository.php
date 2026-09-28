<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Rewardpoints\Plugin\Config;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\ObjectManagerInterface;
use Magestore\Rewardpoints\Model\Rate;
use Magestore\Rewardpoints\Model\ResourceModel\Rate\CollectionFactory;
use Magestore\Webpos\Api\Config\ConfigRepositoryInterface;
use Magestore\Webpos\Api\Data\Config\ConfigExtensionInterfaceFactory;
use Magestore\Webpos\Api\Data\Config\ConfigInterface;

/**
 * Reward points - Class Config
 */
class ConfigRepository
{

    /**
     * @var SearchCriteriaInterface
     */
    protected $searchCriteria;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param CollectionFactory $collectionFactory
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        SearchCriteriaInterface $searchCriteria,
        CollectionFactory $collectionFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchCriteria = $searchCriteria;
        $this->objectManager = $objectManager;
    }

    /**
     * After Get All Config
     *
     * @param ConfigRepositoryInterface $subject
     * @param ConfigInterface $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAllConfig(ConfigRepositoryInterface $subject, $result)
    {
        $extensionAttributes = $result->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->objectManager->get(ConfigExtensionInterfaceFactory::class)->create();
        }

        $extensionAttributes->setRewardpointsRate($this->getRewardpointsRate());
        $result->setExtensionAttributes($extensionAttributes);
        return $result;
    }

    /**
     * After Get Config Path
     *
     * @param ConfigRepositoryInterface $subject
     * @param array $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetConfigPath(ConfigRepositoryInterface $subject, $result)
    {
        $rewardPath = [
            'rewardpoints/general/enable',
            'rewardpoints/general/point_name',
            'rewardpoints/general/point_names',
            'rewardpoints/earning/rounding_method',
            'rewardpoints/earning/max_balance',
            'rewardpoints/earning/by_tax',
            'rewardpoints/earning/by_shipping',
            'rewardpoints/earning/earn_when_spend',
            'rewardpoints/earning/order_invoice',
            'rewardpoints/earning/holding_days',
            'rewardpoints/spending/redeemable_points',
            'rewardpoints/spending/max_points_per_order',
            'rewardpoints/spending/max_point_default',
            'rewardpoints/spending/spend_for_shipping',
        ];
        $newResult = array_merge($result, $rewardPath);
        return $newResult;
    }

    /**
     * Get Reward points Rate
     *
     * @return array
     */
    public function getRewardpointsRate()
    {
        $rates = [];

        $searchCriteria = $this->searchCriteria;
        $searchCriteria->setFilterGroups([]);
        $searchCriteria->setSortOrders([]);
        $searchCriteria->setCurrentPage('');
        $searchCriteria->setPageSize('');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', Rate::STATUS_ACTIVE);
        $collection->addFieldToFilter('points', ['gt' => 0]);
        $collection->addFieldToFilter('money', ['gt' => 0]);
        $collection->setOrder('sort_order', 'ASC');
        $collection->setOrder('rate_id', 'DESC');

        foreach ($collection as $rate) {
            $rates[] = $rate->getData();
        }
        return $rates;
    }
}
