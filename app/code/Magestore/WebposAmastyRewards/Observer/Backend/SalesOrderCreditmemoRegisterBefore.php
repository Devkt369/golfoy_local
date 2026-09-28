<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Observer\Backend;

use Amasty\Rewards\Block\Adminhtml\Sales\Creditmemo as CreditmemoBlock;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - SalesOrderCreditmemoRegisterBefore
 */
class SalesOrderCreditmemoRegisterBefore implements ObserverInterface
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
     * Set reward points balance to creditmemo before register
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $this;
        }
        $input = $observer->getEvent()->getInput();
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        /* @phpstan-ignore-next-line */
        if (!empty($input[CreditmemoBlock::REFUND_KEY]) || !empty($input[CreditmemoBlock::EARNED_POINTS_KEY])) {
            $refundPoints = (float)$input[CreditmemoBlock::REFUND_KEY]; // phpstan:ignore
            $earnedPoints = (float)$input[CreditmemoBlock::EARNED_POINTS_KEY]; // phpstan:ignore
            if ($refundPoints) {
                $creditmemo->setData(CreditmemoBlock::REFUND_KEY, $refundPoints); // phpstan:ignore
                $creditmemo->setAllowZeroGrandTotal(true);
            }
            if ($earnedPoints) {
                $creditmemo->setData(CreditmemoBlock::EARNED_POINTS_KEY, $earnedPoints); // phpstan:ignore
            }
        }

        return $this;
    }
}
