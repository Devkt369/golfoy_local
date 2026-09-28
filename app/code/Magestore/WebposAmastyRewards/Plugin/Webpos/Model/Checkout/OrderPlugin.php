<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Plugin\Webpos\Model\Checkout;

use Magento\Framework\ObjectManagerInterface;
use Magestore\Webpos\Api\Data\Checkout\OrderExtensionInterface;
use Magestore\Webpos\Api\Data\Checkout\OrderExtensionInterfaceFactory;
use Magestore\Webpos\Api\Data\Checkout\OrderInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - Plugin Order
 */
class OrderPlugin
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Data $helper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Data $helper
    ) {
        $this->objectManager = $objectManager;
        $this->helper = $helper;
    }

    /**
     * After Get Extension Attributes
     *
     * @param OrderInterface $order
     * @param OrderExtensionInterface|null $orderExtension
     * @return OrderExtensionInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function afterGetExtensionAttributes(
        OrderInterface $order,
        ?OrderExtensionInterface $orderExtension = null
    ) {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $orderExtension;
        }
        if ($orderExtension === null) {
            $orderExtension = $this->objectManager->get(OrderExtensionInterfaceFactory::class)->create();
        }

        $spentPoints = $orderExtension->getAmSpentRewardPoints();
        if ($spentPoints === null) {
            $spentPoints = (float)$order->getAmSpentRewardPoints();
            $orderExtension->setAmSpentRewardPoints($spentPoints);
        }

        $earnPoints = $orderExtension->getAmEarnRewardPoints();
        if ($earnPoints === null) {
            $earnPoints = (float)$order->getAmEarnRewardPoints();
            $orderExtension->setAmEarnRewardPoints($earnPoints);
        }

        $refundPoints = $orderExtension->getAmRefundRewardPoints();
        if ($refundPoints === null) {
            $refundPoints = (float)$order->getAmRefundRewardPoints();
            $orderExtension->setAmRefundRewardPoints($refundPoints);
        }

        $deductPoints = $orderExtension->getAmDeductRewardPoints();
        if ($deductPoints === null) {
            $deductPoints = (float)$order->getAmDeductRewardPoints();
            $orderExtension->setAmDeductRewardPoints($deductPoints);
        }

        $order->setExtensionAttributes($orderExtension);
        return $orderExtension;
    }
}
