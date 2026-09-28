<?php
namespace Magestore\Rewardpoints\Plugin\Checkout;

use Magento\Framework\ObjectManagerInterface;
use Magestore\Webpos\Api\Data\Checkout\OrderExtensionInterface;
use Magestore\Webpos\Api\Data\Checkout\OrderExtensionInterfaceFactory;
use Magestore\Webpos\Api\Data\Checkout\OrderInterface;

/**
 * Rewardpoints - Plugin Order
 */
class Order
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
     * @param OrderInterface $order
     * @param OrderExtensionInterface|null $orderExtension
     * @return OrderExtensionInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function afterGetExtensionAttributes(
        OrderInterface $order,
        OrderExtensionInterface $orderExtension = null
    ) {
        if ($orderExtension === null) {
            $orderExtension = $this->objectManager->get(OrderExtensionInterfaceFactory::class)->create();
        }

        $rewardpointsSpent = $orderExtension->getRewardpointsSpent();
        if ($rewardpointsSpent === null) {
            $rewardpointsSpent = $order->getRewardpointsSpent();
            $orderExtension->setRewardpointsSpent($rewardpointsSpent);
        }

        $rewardpointsEarn = $orderExtension->getRewardpointsEarn();
        if ($rewardpointsEarn === null) {
            $rewardpointsEarn = $order->getRewardpointsEarn();
            $orderExtension->setRewardpointsEarn($rewardpointsEarn);
        }

        $rewardpointsBaseDiscount = $orderExtension->getRewardpointsBaseDiscount();
        if ($rewardpointsBaseDiscount === null) {
            $rewardpointsBaseDiscount = $order->getRewardpointsBaseDiscount();
            $orderExtension->setRewardpointsBaseDiscount($rewardpointsBaseDiscount);
        }

        $rewardpointsDiscount = $orderExtension->getRewardpointsDiscount();
        if ($rewardpointsDiscount === null) {
            $rewardpointsDiscount = $order->getRewardpointsDiscount();
            $orderExtension->setRewardpointsDiscount($rewardpointsDiscount);
        }

        $rewardpointsBaseAmount = $orderExtension->getRewardpointsBaseAmount();
        if ($rewardpointsBaseAmount === null) {
            $rewardpointsBaseAmount = $order->getRewardpointsBaseAmount();
            $orderExtension->setRewardpointsBaseAmount($rewardpointsBaseAmount);
        }

        $rewardpointsAmount = $orderExtension->getRewardpointsAmount();
        if ($rewardpointsAmount === null) {
            $rewardpointsAmount = $order->getRewardpointsAmount();
            $orderExtension->setRewardpointsAmount($rewardpointsAmount);
        }

        $rewardpointsBaseDiscountForShipping = $orderExtension->getRewardpointsBaseDiscountForShipping();
        if ($rewardpointsBaseDiscountForShipping === null) {
            $rewardpointsBaseDiscountForShipping = $order->getRewardpointsBaseDiscountForShipping();
            $orderExtension->setRewardpointsBaseDiscountForShipping($rewardpointsBaseDiscountForShipping);
        }

        $rewardpointsDiscountForShipping = $orderExtension->getRewardpointsDiscountForShipping();
        if ($rewardpointsDiscountForShipping === null) {
            $rewardpointsDiscountForShipping = $order->getRewardpointsDiscountForShipping();
            $orderExtension->setRewardpointsDiscountForShipping($rewardpointsDiscountForShipping);
        }

        $creditmemoRewardpointsEarn = $orderExtension->getCreditmemoRewardpointsEarn();
        if ($creditmemoRewardpointsEarn === null) {
            $creditmemoRewardpointsEarn = $order->getCreditmemoRewardpointsEarn();
            $orderExtension->setCreditmemoRewardpointsEarn($creditmemoRewardpointsEarn);
        }

        $creditmemoRewardpointsDiscount = $orderExtension->getCreditmemoRewardpointsDiscount();
        if ($creditmemoRewardpointsDiscount === null) {
            $creditmemoRewardpointsDiscount = $order->getCreditmemoRewardpointsDiscount();
            $orderExtension->setCreditmemoRewardpointsDiscount($creditmemoRewardpointsDiscount);
        }

        $creditmemoRewardpointsBaseDiscount = $orderExtension->getCreditmemoRewardpointsBaseDiscount();
        if ($creditmemoRewardpointsBaseDiscount === null) {
            $creditmemoRewardpointsBaseDiscount = $order->getCreditmemoRewardpointsBaseDiscount();
            $orderExtension->setCreditmemoRewardpointsBaseDiscount($creditmemoRewardpointsBaseDiscount);
        }

        $order->setExtensionAttributes($orderExtension);
        return $orderExtension;
    }
}
