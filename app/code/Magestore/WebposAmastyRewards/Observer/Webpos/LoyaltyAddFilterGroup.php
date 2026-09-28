<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Observer\Webpos;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * Observer - Webpos - LoyaltyAddFilterGroup
 */
class LoyaltyAddFilterGroup implements ObserverInterface
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
     * Execute
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return;
        }
        $updatedAt = $observer->getEvent()->getUpdatedAt();
        $updatedAtConditions = $observer->getEvent()->getUpdatedAtConditions();
        $conditions = $updatedAtConditions->getConditions();
        if ($updatedAt) {
            $conditions[] = 'amasty_rewards_rewards.action_date >= "' . $updatedAt . '"';
            $updatedAtConditions->setConditions($conditions);
        }
    }
}
