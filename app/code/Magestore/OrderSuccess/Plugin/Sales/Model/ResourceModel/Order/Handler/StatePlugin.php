<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\OrderSuccess\Plugin\Sales\Model\ResourceModel\Order\Handler;

use Magestore\OrderSuccess\Api\DataProvider\IsGettingStateForOrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Handler\State;
use Magento\Sales\Model\Order;

/**
 * Plugin for StatePlugin
 */
class StatePlugin
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
     * Around Check
     *
     * @param State $subject
     * @param \Closure $proceed
     * @param Order $order
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheck(
        State $subject,
        \Closure $proceed,
        Order $order
    ) {
        $this->isGettingStateForOrder->setIsGettingStateForOrder(1);
        $result = $proceed($order);
        $this->isGettingStateForOrder->setIsGettingStateForOrder(0);
        return $result;
    }
}
