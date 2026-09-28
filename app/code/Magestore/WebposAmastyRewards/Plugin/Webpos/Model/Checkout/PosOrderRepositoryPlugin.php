<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Plugin\Webpos\Model\Checkout;

use Magestore\Webpos\Api\Data\Checkout\OrderInterface;
use Magestore\Webpos\Model\Checkout\PosOrderRepository;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - PosOrderRepositoryPlugin
 */
class PosOrderRepositoryPlugin
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
     * After modify place order params
     *
     * @param PosOrderRepository $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterModifyPlaceOrderParams(
        PosOrderRepository $subject,
        $result
    ) {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $result;
        }
        $order = $result[0];

        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes) {
            if ($extensionAttributes->getAmSpentRewardPoints()) {
                $order->setAmSpentRewardPoints((float)$extensionAttributes->getAmSpentRewardPoints());
            }
            if ($extensionAttributes->getAmEarnRewardPoints()) {
                $order->setAmEarnRewardPoints((float)$extensionAttributes->getAmEarnRewardPoints());
            }
        }

        $result[0] = $order;
        return $result;
    }

    /**
     * Before add item to order
     *
     * @param PosOrderRepository $subject
     * @param OrderInterface $order
     * @param array $items
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAddItemToOrder(
        PosOrderRepository $subject,
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
}
