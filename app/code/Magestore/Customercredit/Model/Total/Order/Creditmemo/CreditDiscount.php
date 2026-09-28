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
 * @package     Magestore_Customercredit
 * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 *
 */

namespace Magestore\Customercredit\Model\Total\Order\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Total\AbstractTotal;

/**
 * Class CreditDiscount
 *
 * Calculate credit memo total
 */
class CreditDiscount extends AbstractTotal
{
    /**
     * Collect total for credit memo
     *
     * @param Creditmemo $creditmemo
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        if ($order->getCustomercreditDiscount() < 0.0001) {
            return;
        }

        $totalDiscountAmount = 0;
        $baseTotalDiscountAmount = 0;

        $totalDiscountRefunded = 0;
        $baseTotalDiscountRefunded = 0;

        foreach ($order->getCreditmemosCollection() as $existedCreditmemo) {
            if ($existedCreditmemo->getCustomercreditDiscount()) {
                $baseTotalDiscountRefunded += $existedCreditmemo->getBaseCustomercreditDiscount();
                $totalDiscountRefunded += $existedCreditmemo->getCustomercreditDiscount();
            }
        }

        $baseShippingAmount = $creditmemo->getBaseShippingAmount();
        if ($baseShippingAmount) {
            $baseTotalDiscountAmount += $baseShippingAmount
                * $order->getBaseCustomercreditDiscountForShipping() / $order->getBaseShippingAmount();
            $totalDiscountAmount += $order->getShippingAmount()
                * $baseTotalDiscountAmount / $order->getBaseShippingAmount();
        }

        foreach ($creditmemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }

            $orderItemBaseDiscount = (float)$orderItem->getBaseCustomercreditDiscount();
            $orderItemDiscount = (float)$orderItem->getCustomercreditDiscount();

            $orderItemQty = $orderItem->getQtyOrdered();
            $refundItemQty = $item->getQty();
            if ($orderItemDiscount && $orderItemQty) {
                $totalDiscountAmount += $creditmemo->roundPrice(
                    $orderItemDiscount / $orderItemQty * $refundItemQty,
                    'regular',
                    true
                );
                $baseTotalDiscountAmount += $creditmemo->roundPrice(
                    $orderItemBaseDiscount / $orderItemQty * $refundItemQty,
                    'base',
                    true
                );
            }
        }
        $creditmemo->setBaseCustomercreditDiscount($baseTotalDiscountAmount);
        $creditmemo->setCustomercreditDiscount($totalDiscountAmount);

        $creditmemo->setAllowZeroGrandTotal(true);

        $grandTotal = abs($creditmemo->getGrandTotal()) < 0.0001 ? 0 : $creditmemo->getGrandTotal();
        $baseGrandTotal = abs($creditmemo->getBaseGrandTotal()) < 0.0001 ? 0 : $creditmemo->getBaseGrandTotal();
        $creditmemo->setGrandTotal($grandTotal);
        $creditmemo->setBaseGrandTotal($baseGrandTotal);
    }

    /**
     * Check credit memo is last or not
     *
     * @param Creditmemo $creditmemo
     * @return boolean
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
