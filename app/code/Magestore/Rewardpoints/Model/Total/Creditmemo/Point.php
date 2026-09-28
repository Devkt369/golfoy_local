<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_RewardPoints
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Rewardpoints\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Class Point
 *
 * Used to calculate points
 */
class Point extends AbstractTotal
{
    /**
     * Collect credit memo total
     *
     * @param Creditmemo $creditmemo
     * @return $this|void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collect(Creditmemo $creditmemo)
    {
        $creditmemo->setRewardpointsDiscount(0);
        $creditmemo->setRewardpointsBaseDiscount(0);

        $order = $creditmemo->getOrder();

        if ($order->getRewardpointsDiscount() < 0.0001) {
            return;
        }

        $totalDiscountAmount = 0;
        $baseTotalDiscountAmount = 0;
        $baseTotalDiscountRefunded = 0;
        $totalDiscountRefunded = 0;
        foreach ($order->getCreditmemosCollection() as $existedCreditmemo) {
            if ($existedCreditmemo->getRewardpointsDiscount()) {
                $totalDiscountRefunded += $existedCreditmemo->getRewardpointsDiscount();
                $baseTotalDiscountRefunded += $existedCreditmemo->getRewardpointsBaseDiscount();
            }
        }

        /**
         * Calculate how much shipping discount should be applied
         * basing on how much shipping should be refunded.
         */
        $baseShippingAmount = (float)$creditmemo->getBaseShippingAmount();
        if ($baseShippingAmount) {
            $baseTotalDiscountAmount = $baseShippingAmount
                * $order->getRewardpointsBaseDiscountForShipping() / $order->getBaseShippingAmount();
            $totalDiscountAmount = $creditmemo->getShippingAmount()
                * $order->getRewardpointsDiscountForShipping() / $order->getShippingAmount();
        }

        if ($this->isLast($creditmemo)) {
            $baseTotalDiscountAmount = $order->getRewardpointsBaseDiscount() - $baseTotalDiscountRefunded;
            $totalDiscountAmount = $order->getRewardpointsDiscount() - $totalDiscountRefunded;
        } else {
            foreach ($creditmemo->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->isDummy()) {
                    continue;
                }
                $orderItemDiscount = (float)$orderItem->getRewardpointsDiscount()
                    * $orderItem->getQtyInvoiced() / $orderItem->getQtyOrdered();
                $baseOrderItemDiscount = (float)$orderItem->getRewardpointsBaseDiscount()
                    * $orderItem->getQtyInvoiced() / $orderItem->getQtyOrdered();

                $orderItemQty = $orderItem->getQtyInvoiced();

                if ($orderItemDiscount && $orderItemQty) {
                    $totalDiscountAmount += $creditmemo->roundPrice(
                        $orderItemDiscount / $orderItemQty * $item->getQty(),
                        'regular',
                        true
                    );
                    $baseTotalDiscountAmount += $creditmemo->roundPrice(
                        $baseOrderItemDiscount / $orderItemQty * $item->getQty(),
                        'base',
                        true
                    );
                }
            }
        }

        $creditmemo->setRewardpointsDiscount($totalDiscountAmount);
        $creditmemo->setRewardpointsBaseDiscount($baseTotalDiscountAmount);

        $grandTotal = abs($creditmemo->getGrandTotal()) < 0.0001 ? 0 : $creditmemo->getGrandTotal();
        $baseGrandTotal = abs($creditmemo->getBaseGrandTotal()) < 0.0001 ? 0 : $creditmemo->getBaseGrandTotal();
        $creditmemo->setGrandTotal($grandTotal);
        $creditmemo->setBaseGrandTotal($baseGrandTotal);

        return $this;
    }

    /**
     * Check credit memo is last or not
     *
     * @param Creditmemo $creditmemo
     * @return bool
     */
    public function isLast($creditmemo)
    {
        foreach ($creditmemo->getAllItems() as $item) {
            if (!$item->isLast()) {
                return false;
            }
        }
        return true;
    }
}
