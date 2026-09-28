<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\OrderSuccess\Plugin\Sales\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\OrderStateResolverInterface;
use Magestore\OrderSuccess\Api\DataProvider\IsGettingStateForOrderInterface;

/**
 * Plugin for OrderStateResolverInterface
 */
class OrderStateResolverPlugin
{
    /**
     * @var IsGettingStateForOrderInterface
     */
    protected $isGettingStateForOrder;

    /**
     * OrderStateResolverPlugin constructor.
     *
     * @param IsGettingStateForOrderInterface $isGettingStateForOrder
     */
    public function __construct(
        IsGettingStateForOrderInterface $isGettingStateForOrder
    ) {
        $this->isGettingStateForOrder = $isGettingStateForOrder;
    }

    /**
     * Around Get State For Order
     *
     * @param OrderStateResolverInterface $subject
     * @param \Closure $proceed
     * @param OrderInterface $order
     * @param array $arguments
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetStateForOrder(
        OrderStateResolverInterface $subject,
        \Closure $proceed,
        OrderInterface $order,
        array $arguments = []
    ) {
        $this->isGettingStateForOrder->setIsGettingStateForOrder(1);
        $result = $proceed($order, $arguments);
        $this->isGettingStateForOrder->setIsGettingStateForOrder(0);
        return $result;
    }
}
