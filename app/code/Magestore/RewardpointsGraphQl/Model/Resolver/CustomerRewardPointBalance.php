<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model\Resolver;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magestore\Rewardpoints\Helper\Config as RewardPointConfig;
use Magestore\Rewardpoints\Helper\Customer as CustomerHelper;

/**
 * Fetch customer reward points
 */
class CustomerRewardPointBalance implements ResolverInterface
{
    const XML_ENABLE = 'enable';
    const MS_REWARD_POINT_BALANCE = 'ms_reward_point_balance';

    /**
     * @var RewardPointConfig
     */
    private $config;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var CustomerHelper
     */
    private $customerHelper;

    /**
     * CustomerRewardPoints constructor.
     *
     * @param RewardPointConfig $config
     * @param CustomerRegistry $customerRegistry
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        RewardPointConfig $config,
        CustomerRegistry $customerRegistry,
        CustomerHelper $customerHelper
    ) {
        $this->config = $config;
        $this->customerRegistry = $customerRegistry;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $currentStoreId = (int)$context->getExtensionAttributes()->getStore()->getId();

        if (!(int) $this->config->getGeneralConfig(self::XML_ENABLE, $currentStoreId)) {
            return 0;
        }

        /** @var Customer $customer */
        $currentCustomer = $this->customerRegistry->retrieve($context->getUserId());
        if (!$currentCustomer && !$currentCustomer->getId()) {
            throw new GraphQlInputException(
                __('Something went wrong while loading the customer.')
            );
        }

        $customerAccountReward = $this->customerHelper->getAccountByCustomer($currentCustomer);
        return (int) $customerAccountReward->getPointBalance();
    }
}
