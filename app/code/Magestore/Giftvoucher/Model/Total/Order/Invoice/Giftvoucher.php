<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Giftvoucher\Model\Total\Order\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Total\AbstractTotal;

/**
 * Class Giftvoucher
 *
 * Used to calculate invoice
 */
class Giftvoucher extends AbstractTotal
{

    /**
     * Collect invoice giftvoucher
     *
     * @param Invoice $invoice
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        if (!$order->getGiftVoucherDiscount()) {
            return $this;
        }

        $baseTotalDiscountAmountGiftvoucher = 0;
        $totalDiscountAmountGiftvoucher = 0;

        $totalGiftvoucherDiscountInvoiced = 0;
        $baseTotalGiftvoucherDiscountInvoiced = 0;

        $addShippingDiscount = true;
        foreach ($invoice->getOrder()->getInvoiceCollection() as $previusInvoice) {
            if ($previusInvoice->getGiftVoucherDiscount()) {
                $addShippingDiscount = false;
                $totalGiftvoucherDiscountInvoiced += $previusInvoice->getGiftVoucherDiscount();
                $baseTotalGiftvoucherDiscountInvoiced += $previusInvoice->getBaseGiftVoucherDiscount();
            }
        }

        if ($addShippingDiscount) {
            $totalDiscountAmountGiftvoucher = $order->getGiftvoucherDiscountForShipping();
            $baseTotalDiscountAmountGiftvoucher = $order->getBaseGiftvoucherDiscountForShipping();
        }

        if ($invoice->isLast()) {
            $totalDiscountAmountGiftvoucher = $order->getGiftVoucherDiscount() - $totalGiftvoucherDiscountInvoiced;
            $baseTotalDiscountAmountGiftvoucher = $order->getBaseGiftVoucherDiscount()
                - $baseTotalGiftvoucherDiscountInvoiced;
        } else {
            foreach ($invoice->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->isDummy()) {
                    continue;
                }
                $orderItemDiscountGiftvoucher = (float)$orderItem->getGiftVoucherDiscount();
                $baseOrderItemDiscountGiftvoucher = (float)$orderItem->getBaseGiftVoucherDiscount();

                $orderItemQty = $orderItem->getQtyOrdered();
                $invoiceItemQty = $item->getQty();

                if ($orderItemDiscountGiftvoucher && $orderItemQty) {
                    $discount = $invoice->roundPrice(
                        $orderItemDiscountGiftvoucher / $orderItemQty * $invoiceItemQty,
                        'regular',
                        false
                    );
                    $baseDiscount = $invoice->roundPrice(
                        $baseOrderItemDiscountGiftvoucher / $orderItemQty * $invoiceItemQty,
                        'base',
                        false
                    );
                    $totalDiscountAmountGiftvoucher += $discount;
                    $baseTotalDiscountAmountGiftvoucher += $baseDiscount;
                }
            }
        }

        $invoice->setBaseGiftVoucherDiscount($baseTotalDiscountAmountGiftvoucher);
        $invoice->setGiftVoucherDiscount($totalDiscountAmountGiftvoucher);

        $grandTotal = abs($invoice->getGrandTotal()) < 0.0001 ? 0 : $invoice->getGrandTotal();
        $baseGrandTotal = abs($invoice->getBaseGrandTotal()) < 0.0001 ? 0 : $invoice->getBaseGrandTotal();
        $invoice->setGrandTotal($grandTotal);
        $invoice->setBaseGrandTotal($baseGrandTotal);

        return $this;
    }
}
