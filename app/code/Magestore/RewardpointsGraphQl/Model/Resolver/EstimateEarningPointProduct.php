<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magestore\Rewardpoints\Helper\Calculation\Earning as EarningHelper;
use Magestore\Rewardpoints\Helper\Config as RewardPointConfig;
use Magento\Customer\Model\Group as CustomerGroup;

/**
 * Class EstimateEarningPointProduct
 *
 * Get earning point from product
 */
class EstimateEarningPointProduct implements ResolverInterface
{
    const XML_ENABLE = 'enable';

    /**
     * @var RewardPointConfig
     */
    private $config;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var EarningHelper
     */
    private $earningHelper;

    /**
     * CustomerRewardPoints constructor.
     *
     * @param RewardPointConfig $config
     * @param CustomerRegistry $customerRegistry
     * @param EarningHelper $earningHelper
     */
    public function __construct(
        RewardPointConfig $config,
        CustomerRegistry $customerRegistry,
        EarningHelper $earningHelper
    ) {
        $this->config = $config;
        $this->customerRegistry = $customerRegistry;
        $this->earningHelper = $earningHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentWebsiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsite()->getId();
        $currentStore = $context->getExtensionAttributes()->getStore();

        if (!(int) $this->config->getGeneralConfig(self::XML_ENABLE, $currentStore->getId())) {
            return 0;
        }

        if (!array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if ($context->getUserId()) {
            /** @var Customer $currentCustomer */
            $currentCustomer = $this->customerRegistry->retrieve($context->getUserId());
            if (!$currentCustomer && !$currentCustomer->getId()) {
                throw new GraphQlInputException(
                    __('Something went wrong while loading the customer.')
                );
            }
            $customerGroupId = $currentCustomer->getGroupId();
        } else {
            $customerGroupId = CustomerGroup::NOT_LOGGED_IN_ID;
        }

        $product = $value['model'];

        try {
            if (!$product->getFinalPrice()) {
                $product = $product->load($product->getId());
            }
            $amount = (float) $product->getFinalPrice();

            return (int) $this->earningHelper->getRateEarningPointByAmount(
                $amount,
                (int) $customerGroupId,
                $currentWebsiteId,
                $currentStore
            );
        } catch (\Exception $exception) {
            return 0;
        }
    }
}
