<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Observer\Webpos\Order;

use Amasty\Rewards\Api\Data\SalesQuote\EntityInterface;
use Amasty\Rewards\Api\RewardsProviderInterface;
use Amasty\Rewards\Api\RewardsRepositoryInterface;
use Amasty\Rewards\Model\Config\Source\Actions;
use Amasty\Rewards\Model\RewardsProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - SalesOrderSaveAfter
 */
class SalesOrderSaveAfter implements ObserverInterface
{

    /**
     * @var RewardsProviderInterface
     */
    protected $rewardsProvider;

    /**
     * @var RewardsRepositoryInterface
     */
    protected $rewardsRepository;

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
        if ($this->helper->isEnableAmastyRewards()) {
            $this->rewardsProvider =
                ObjectManager::getInstance()->get(RewardsProviderInterface::class); // phpstan:ignore
            $this->rewardsRepository =
                ObjectManager::getInstance()->get(RewardsRepositoryInterface::class); // phpstan:ignore
        }
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $this;
        }
        $order = $observer['order'];
        if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
            return $this;
        }

        // check order is placed by web pos pwa
        if (!$order->getPosLocationId()) {
            return $this;
        }

        // Process spending points for order
        if ($order->getAmSpentRewardPoints() > 0) {
            $modelRewards = $this->rewardsRepository->getEmptyModel();
            $modelRewards->setCustomerId((int)$order->getCustomerId());

            $comment = __('Order #%1', $order->getIncrementId())->render();
            $modelRewards->setAmount((float)$order->getData(EntityInterface::POINTS_SPENT)); // phpstan:ignore
            $modelRewards->setComment($comment);
            $modelRewards->setAction(Actions::REWARDS_SPEND_ACTION); // phpstan:ignore
            $modelRewards->setVisibleForCustomer(RewardsProvider::EVERYTIME_VISIBLE_FOR_CUSTOMER); // phpstan:ignore
            if ($modelRewards->getAmount() > 0) {
                $this->rewardsProvider->deductRewardPoints($modelRewards);
            }
        }
        return $this;
    }
}
