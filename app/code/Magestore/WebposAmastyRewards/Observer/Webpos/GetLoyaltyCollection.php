<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Observer\Webpos;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magestore\WebposAmastyRewards\Helper\Data;
use Zend_Db_Expr;

/**
 * Observer - Webpos - Get Loyalty Collection
 */
class GetLoyaltyCollection implements ObserverInterface
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
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $this;
        }
        $collection = $observer->getEvent()->getCollection();
        $collection->getSelect()->joinLeft(
            ['amasty_rewards_rewards' => $this->getAmastyRewardsSelect($collection)],
            'e.entity_id = amasty_rewards_rewards.customer_id',
            [
                'amasty_rewards_action_date' => 'amasty_rewards_rewards.action_date',
                'amasty_reward_balance' => 'amasty_rewards_rewards.points_left'
            ]
        );
        return $this;
    }

    /**
     * Get Amasty Rewards Select
     *
     * @param Collection $collection
     * @return Select
     */
    public function getAmastyRewardsSelect(Collection $collection)
    {
        $connection = $collection->getConnection();
        $lastSelect = $connection->select();
        $lastSelect->from(
            $collection->getTable('amasty_rewards_rewards'),
            ['id' => new Zend_Db_Expr('MAX(id)')]
        )->group('customer_id');

        $select = $connection->select();
        $select->from(
            [
                'amasty_rewards_rewards' => $collection->getTable('amasty_rewards_rewards'),
            ]
        )->joinInner(
            ['last_rewards' => $lastSelect],
            'amasty_rewards_rewards.id = last_rewards.id',
            []
        );
        return $select;
    }
}
