<?php
namespace Magestore\Rewardpoints\Plugin\Checkout\Order;

use Magento\Framework\ObjectManagerInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\ItemExtensionInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\ItemExtensionInterfaceFactory;
use Magestore\Webpos\Api\Data\Checkout\Order\ItemInterface;

/**
 * Rewardpoints - Plugin Checkout Order Item
 */
class Item
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
     * @param ItemInterface $item
     * @param ItemExtensionInterface|null $itemExtension
     * @return mixed
     */
    public function afterGetExtensionAttributes(
        ItemInterface $item,
        ItemExtensionInterface $itemExtension = null
    ) {
        if ($itemExtension === null) {
            $itemExtension = $this->objectManager->get(ItemExtensionInterfaceFactory::class)->create();
        }

        $rewardpointsSpent = $itemExtension->getRewardpointsSpent();
        if ($rewardpointsSpent === null) {
            $rewardpointsSpent = $item->getRewardpointsSpent();
            $itemExtension->setRewardpointsSpent($rewardpointsSpent);
        }

        $rewardpointsEarn = $itemExtension->getRewardpointsEarn();
        if ($rewardpointsEarn === null) {
            $rewardpointsEarn = $item->getRewardpointsEarn();
            $itemExtension->setRewardpointsEarn($rewardpointsEarn);
        }

        $rewardpointsDiscount = $itemExtension->getRewardpointsDiscount();
        if ($rewardpointsDiscount === null) {
            $rewardpointsDiscount = $item->getRewardpointsDiscount();
            $itemExtension->setRewardpointsDiscount($rewardpointsDiscount);
        }

        $rewardpointsBaseDiscount = $itemExtension->getRewardpointsBaseDiscount();
        if ($rewardpointsBaseDiscount === null) {
            $rewardpointsBaseDiscount = $item->getRewardpointsBaseDiscount();
            $itemExtension->setRewardpointsBaseDiscount($rewardpointsBaseDiscount);
        }

        $item->setExtensionAttributes($itemExtension);
        return $itemExtension;
    }
}
