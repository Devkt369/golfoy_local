<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Plugin\Webpos\Model\Config;

use Magestore\Webpos\Api\Config\ConfigRepositoryInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * Webpos - ConfigRepositoryPlugin
 */
class ConfigRepositoryPlugin
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
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
        if (!$this->helper->isEnableAmastyRewards()) {
            return $result;
        }
        $rewardPath = [
            'amrewards/general/enabled',
            'amrewards/points/rate',
            'amrewards/points/round_rule',
            'amrewards/calculation/before_after_tax',
            'amrewards/points/minimum_reward',
            'amrewards/points/disable_reward',
            'amrewards/points/enable_limit',
            'amrewards/points/limit_amount_reward',
            'amrewards/points/limit_percent_reward',
            'amrewards/points/read_only',
            'amrewards/spending_config/specific_products_enabled',
            'amrewards/spending_config/action',
            'amrewards/spending_config/sku',
            'amrewards/spending_config/category',
            'amrewards/spending_config/block_tooltip_enabled',
            'amrewards/spending_config/block_tooltip_text',
            'amrewards/customer/description_enable',
            'amrewards/customer/description_message',
            'amrewards/customer/show_balance',
            'amrewards/customer/balance_label',
            'amrewards/reward_program_restriction/message_enabled',
            'amrewards/reward_program_restriction/message_text',
            'amrewards/order/show_detailed'
        ];
        $newResult = array_merge($result, $rewardPath);
        return $newResult;
    }
}
