<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Plugin\Webpos\Model\Checkout\Order;

use Magento\Framework\ObjectManagerInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\ItemExtensionInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\ItemExtensionInterfaceFactory;
use Magestore\Webpos\Api\Data\Checkout\Order\ItemInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - Plugin Checkout Order Item
 */
class ItemPlugin
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
     * @param ItemInterface $item
     * @param ItemExtensionInterface|null $itemExtension
     * @return mixed
     */
    public function afterGetExtensionAttributes(
        ItemInterface $item,
        ?ItemExtensionInterface $itemExtension = null
    ) {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $itemExtension;
        }
        if ($itemExtension === null) {
            $itemExtension = $this->objectManager->get(ItemExtensionInterfaceFactory::class)->create();
        }

        $spentPoints = $itemExtension->getAmSpentRewardPoints();
        if ($spentPoints === null) {
            $spentPoints = (float)$item->getAmSpentRewardPoints();
            $itemExtension->setAmSpentRewardPoints($spentPoints);
        }

        $earnPoints = $itemExtension->getAmEarnRewardPoints();
        if ($earnPoints === null) {
            $earnPoints = (float)$item->getAmEarnRewardPoints();
            $itemExtension->setAmEarnRewardPoints($earnPoints);
        }

        $item->setExtensionAttributes($itemExtension);
        return $itemExtension;
    }
}
