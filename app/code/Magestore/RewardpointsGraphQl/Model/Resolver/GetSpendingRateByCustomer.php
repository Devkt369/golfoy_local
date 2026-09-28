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
use Magestore\Rewardpoints\Model\GetRateByCustomer;
use Magestore\Rewardpoints\Model\Rate;

/**
 * Fetch customer reward points
 */
class GetSpendingRateByCustomer implements ResolverInterface
{
    const MS_SPENDING_RATE = 'ms_spending_rate';

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
     * @var GetRateByCustomer
     */
    private $getRateByCustomer;

    /**
     * CustomerRewardPoints constructor.
     *
     * @param RewardPointConfig $config
     * @param CustomerRegistry $customerRegistry
     * @param CustomerHelper $customerHelper
     * @param GetRateByCustomer $getRateByCustomer
     */
    public function __construct(
        RewardPointConfig $config,
        CustomerRegistry $customerRegistry,
        CustomerHelper $customerHelper,
        GetRateByCustomer $getRateByCustomer
    ) {
        $this->config = $config;
        $this->customerRegistry = $customerRegistry;
        $this->customerHelper = $customerHelper;
        $this->getRateByCustomer = $getRateByCustomer;
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

        if (!(int) $this->config->getGeneralConfig(CustomerRewardPointBalance::XML_ENABLE, $currentStoreId)) {
            return 0;
        }

        /** @var Customer $customer */
        $currentCustomer = $this->customerRegistry->retrieve($context->getUserId());
        if (!$currentCustomer && !$currentCustomer->getId()) {
            throw new GraphQlInputException(
                __('Something went wrong while loading the customer.')
            );
        }

        return $this->getRateByCustomer->execute(
            Rate::POINT_TO_MONEY,
            $currentCustomer->getGroupId(),
            $currentCustomer->getWebsiteId()
        );
    }
}
