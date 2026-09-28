<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magestore\Rewardpoints\Helper\Config as RewardPointConfig;
use Magestore\Rewardpoints\Model\Customer;
use Magestore\Rewardpoints\Model\CustomerFactory;
use Magestore\Rewardpoints\Model\ResourceModel\Customer as CustomerResource;

/**
 * Fetch customer reward points settings
 */
class GetRewardCustomerSettings implements ResolverInterface
{
    const XML_ENABLE = 'enable';

    /**
     * @var RewardPointConfig
     */
    private $config;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * SaveRewardCustomerSettings constructor.
     *
     * @param RewardPointConfig $config
     * @param CustomerFactory $customerFactory
     * @param CustomerResource $customerResource
     */
    public function __construct(
        RewardPointConfig $config,
        CustomerFactory $customerFactory,
        CustomerResource  $customerResource
    ) {
        $this->config = $config;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
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
            throw new LocalizedException(
                __("Reward point is not active.")
            );
        }

        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current customer isn\'t authorized.')
            );
        }

        $customerId = $context->getUserId();
        /* @var Customer $customerReward */
        $customerReward = $this->customerFactory->create();
        $this->customerResource->load($customerReward, $customerId, 'customer_id');
        if (!$customerReward->getId()) {
            /* Return default value is Yes */
            return [
                'is_notification' => true,
                'expire_notification' => true
            ];
        }
        return $customerReward;
    }
}
