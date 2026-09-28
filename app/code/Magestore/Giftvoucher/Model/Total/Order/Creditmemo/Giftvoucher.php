<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Giftvoucher\Model\Total\Order\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Total\AbstractTotal;

/**
 * Class Giftvoucher
 *
 * Calculate credit memo of giftvoucher
 */
class Giftvoucher extends AbstractTotal
{

    /**
     * Collect creditmemo giftvoucher
     *
     * @param Creditmemo $creditmemo
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        if (!$order->getGiftVoucherDiscount()) {
            return $this;
        }

        $totalDiscountAmountGiftvoucher = 0;
        $baseTotalDiscountAmountGiftvoucher = 0;

        $totalGiftvoucherDiscountRefunded = 0;
        $baseGiftvoucherTotalDiscountRefunded = 0;

        foreach ($order->getCreditmemosCollection() as $existedCreditmemo) {
            if ($existedCreditmemo->getGiftVoucherDiscount()) {
                $totalGiftvoucherDiscountRefunded += $existedCreditmemo->getGiftVoucherDiscount();
                $baseGiftvoucherTotalDiscountRefunded += $existedCreditmemo->getBaseGiftVoucherDiscount();
            }
        }

        $baseShippingAmount = $creditmemo->getBaseShippingAmount();
        if ($baseShippingAmount) {
            $baseTotalDiscountAmountGiftvoucher = $baseShippingAmount * $order->getBaseGiftvoucherDiscountForShipping()
                / $order->getBaseShippingAmount();
            $totalDiscountAmountGiftvoucher = $creditmemo->getShippingAmount()
                * $order->getGiftvoucherDiscountForShipping()
                / $order->getShippingAmount();
            /* set base total gift_code discount amount for shipping */
            $creditmemo->setBaseTotalGiftcodeDiscountAmountForShipping($baseTotalDiscountAmountGiftvoucher);
        }

        foreach ($creditmemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }

            $orderItemDiscountGiftvoucher = (float)$orderItem->getGiftVoucherDiscount();
            $baseOrderItemDiscountGiftvoucher = (float)$orderItem->getBaseGiftVoucherDiscount();

            $orderItemQty = $orderItem->getQtyOrdered();
            $creditmemoItemQty = $item->getQty();

            if ($orderItemDiscountGiftvoucher && $orderItemQty) {
                $discount = $creditmemo->roundPrice(
                    $orderItemDiscountGiftvoucher / $orderItemQty * $creditmemoItemQty,
                    'regular',
                    false
                );
                $baseDiscount = $creditmemo->roundPrice(
                    $baseOrderItemDiscountGiftvoucher / $orderItemQty * $creditmemoItemQty,
                    'base',
                    false
                );

                $totalDiscountAmountGiftvoucher += $discount;
                $baseTotalDiscountAmountGiftvoucher += $baseDiscount;
            }
        }

        $creditmemo->setBaseGiftVoucherDiscount($baseTotalDiscountAmountGiftvoucher);
        $creditmemo->setGiftVoucherDiscount($totalDiscountAmountGiftvoucher);

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
