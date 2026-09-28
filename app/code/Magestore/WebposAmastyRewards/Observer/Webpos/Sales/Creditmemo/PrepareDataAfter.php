<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Observer\Webpos\Sales\Creditmemo;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - Creditmemo - PrepareDataAfter
 */
class PrepareDataAfter implements ObserverInterface
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
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $this;
        }
        $creditmemo = $observer->getEvent()->getData('creditmemo');
        $result = $observer->getResult();
        $data = $result->getData();

        $data['creditmemo']['reward_points_to_refund'] =
            $creditmemo->getExtensionAttributes()->getRewardPointsToRefund();
        $data['creditmemo']['earned_points'] = $creditmemo->getExtensionAttributes()->getEarnedPoints();

        $result->setData($data);

        return $this;
    }
}
