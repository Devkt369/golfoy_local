<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Plugin\Webpos\Model\Checkout;

use Magestore\Webpos\Api\Checkout\CheckoutRepositoryInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\ItemInterface;
use Magestore\Webpos\Api\Data\Checkout\OrderInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - CheckoutRepositoryPlugin
 */
class CheckoutRepositoryPlugin
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
     * Before Place Order
     *
     * @param CheckoutRepositoryInterface $subject
     * @param OrderInterface $order
     * @param int $create_shipment
     * @param int $create_invoice
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePlaceOrder(
        CheckoutRepositoryInterface $subject,
        OrderInterface $order,
        $create_shipment,
        $create_invoice
    ) {
        if ($this->helper->isEnableAmastyRewards()) {
            $this->populateExtensionData($order);
        }
    }

    /**
     * Before Add Item To Order
     *
     * @param CheckoutRepositoryInterface $subject
     * @param OrderInterface $order
     * @param ItemInterface[] $items
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAddItemToOrder(
        CheckoutRepositoryInterface $subject,
        $order,
        $items
    ) {
        if ($this->helper->isEnableAmastyRewards()) {
            foreach ($items as &$item) {
                $extensionAttributes = $item->getExtensionAttributes();
                if ($extensionAttributes->getAmSpentRewardPoints()) {
                    $item->setAmSpentRewardPoints((float)$extensionAttributes->getAmSpentRewardPoints());
                }
                if ($extensionAttributes->getAmEarnRewardPoints()) {
                    $item->setAmEarnRewardPoints((float)$extensionAttributes->getAmEarnRewardPoints());
                }
            }
        }
    }

    /**
     * Before Hold Order
     *
     * @param CheckoutRepositoryInterface $subject
     * @param OrderInterface $order
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeHoldOrder(
        CheckoutRepositoryInterface $subject,
        OrderInterface $order
    ) {
        if ($this->helper->isEnableAmastyRewards()) {
            $this->populateExtensionData($order);
        }
    }

    /**
     * Populate Extension Data
     *
     * @param OrderInterface $order
     * @return mixed
     */
    public function populateExtensionData($order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes->getAmSpentRewardPoints()) {
            $order->setAmSpentRewardPoints((float)$extensionAttributes->getAmSpentRewardPoints());
        }
        if ($extensionAttributes->getAmEarnRewardPoints()) {
            $order->setAmEarnRewardPoints((float)$extensionAttributes->getAmEarnRewardPoints());
        }
        return $order;
    }
}
