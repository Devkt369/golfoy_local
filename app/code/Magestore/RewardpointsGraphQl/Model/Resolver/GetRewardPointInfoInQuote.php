<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use Magestore\Rewardpoints\Helper\Config as RewardPointConfig;

/**
 * Get reward point info for quote
 */
class GetRewardPointInfoInQuote implements ResolverInterface
{
    const XML_ENABLE = 'enable';

    /**
     * @var RewardPointConfig
     */
    private $config;

    /**
     * GetRewardPointInfoInQuote constructor.
     *
     * @param RewardPointConfig $config
     */
    public function __construct(
        RewardPointConfig $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentStore = $context->getExtensionAttributes()->getStore();

        if (!(int) $this->config->getGeneralConfig(self::XML_ENABLE, $currentStore->getId())) {
            return 0;
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Quote $quote */
        $quote = $value['model'];

        return [
            'rewardpoints_earn' => (int) $quote->getData('rewardpoints_earn'),
            'use_point' => (int) $quote->getData('use_point'),
            'use_max_point' => (int) $quote->getData('use_max_point'),
            'reward_checked_rules' => $quote->getData('reward_checked_rules'),
            'reward_sales_rules' => $quote->getData('reward_sales_rules'),
            'rewardpoints_spent' => (float) $quote->getData('rewardpoints_spent'),
            'rewardpoints_discount' => (float) $quote->getData('rewardpoints_discount'),
            'rewardpoints_base_discount' => (float) $quote->getData('rewardpoints_base_discount'),
            'rewardpoints_discount_for_shipping' =>
                (float) $quote->getData('rewardpoints_discount_for_shipping'),
            'rewardpoints_base_discount_for_shipping' =>
                (float) $quote->getData('rewardpoints_base_discount_for_shipping'),
        ];
    }
}
