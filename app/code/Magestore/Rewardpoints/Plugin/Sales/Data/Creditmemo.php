<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Rewardpoints\Plugin\Sales\Data;

use Magento\Framework\ObjectManagerInterface;
use Magestore\Webpos\Api\Data\Sales\Order\CreditmemoExtensionInterface;
use Magestore\Webpos\Api\Data\Sales\Order\CreditmemoExtensionInterfaceFactory;
use Magestore\Webpos\Api\Data\Sales\Order\CreditmemoInterface;

/**
 * Plugin - Creditmemo
 */
class Creditmemo
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * After Get Extension Attributes
     *
     * @param CreditmemoInterface $creditmemo
     * @param CreditmemoExtensionInterface|null $creditmemoExtension
     * @return CreditmemoExtensionInterface
     */
    public function afterGetExtensionAttributes(
        CreditmemoInterface $creditmemo,
        CreditmemoExtensionInterface $creditmemoExtension = null
    ) {
        if ($creditmemoExtension === null) {
            $creditmemoExtension = $this->objectManager->get(CreditmemoExtensionInterfaceFactory::class)->create();
        }

        $rewardpointsDiscount = $creditmemoExtension->getRewardpointsDiscount();
        if ($rewardpointsDiscount === null) {
            $rewardpointsDiscount = $creditmemo->getRewardpointsDiscount();
            $creditmemoExtension->setRewardpointsDiscount($rewardpointsDiscount);
        }

        $rewardpointsBaseDiscount = $creditmemoExtension->getRewardpointsBaseDiscount();
        if ($rewardpointsBaseDiscount === null) {
            $rewardpointsBaseDiscount = $creditmemo->getRewardpointsBaseDiscount();
            $creditmemoExtension->setRewardpointsBaseDiscount($rewardpointsBaseDiscount);
        }

        $rewardpointsEarn = $creditmemoExtension->getRewardpointsEarn();
        if ($rewardpointsEarn === null) {
            $rewardpointsEarn = $creditmemo->getRewardpointsEarn();
            $creditmemoExtension->setRewardpointsEarn($rewardpointsEarn);
        }

        $refundEarnPoint = $creditmemoExtension->getRefundEarnedPoints();
        if ($refundEarnPoint === null) {
            $refundEarnPoint = $creditmemo->getRefundEarnedPoints();
            $creditmemoExtension->setRefundEarnedPoints($refundEarnPoint);
        }

        $refundPoint = $creditmemoExtension->getRefundPoints();
        if ($refundPoint === null) {
            $refundPoint = $creditmemo->getRefundPoints();
            $creditmemoExtension->setRefundPoints($refundPoint);
        }

        $creditmemo->setExtensionAttributes($creditmemoExtension);
        return $creditmemoExtension;
    }
}
