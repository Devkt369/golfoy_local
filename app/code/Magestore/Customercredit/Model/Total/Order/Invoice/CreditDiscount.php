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

namespace Magestore\Customercredit\Model\Total\Order\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Total\AbstractTotal;

/**
 * Class CreditDiscount
 *
 * Used to calculate invoice total
 */
class CreditDiscount extends AbstractTotal
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
        if ($order->getCustomercreditDiscount() < 0.0001) {
            return $this;
        }

        $totalDiscountInvoiced = 0;
        $totalBaseDiscountInvoiced = 0;

        $totalDiscountAmount = 0;
        $totalBaseDiscountAmount = 0;
        $checkAddShipping = true;

        foreach ($order->getInvoiceCollection() as $previousInvoice) {
            if ($previousInvoice->getCustomercreditDiscount()) {
                $checkAddShipping = false;
                $totalBaseDiscountInvoiced += $previousInvoice->getBaseCustomercreditDiscount();
                $totalDiscountInvoiced += $previousInvoice->getCustomercreditDiscount();
            }
        }

        if ($checkAddShipping) {
            $totalBaseDiscountAmount += $order->getBaseCustomercreditDiscountForShipping();
            $totalDiscountAmount += $order->getCustomercreditDiscountForShipping();
        }

        if ($invoice->isLast()) {
            $totalBaseDiscountAmount = $order->getBaseCustomercreditDiscount() - $totalBaseDiscountInvoiced;
            $totalDiscountAmount = $order->getCustomercreditDiscount() - $totalDiscountInvoiced;
        } else {
            foreach ($invoice->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->isDummy()) {
                    continue;
                }
                $baseOrderItemCustomercreditDiscount = (float)$orderItem->getBaseCustomercreditDiscount();
                $orderItemCustomercreditDiscount = (float)$orderItem->getCustomercreditDiscount();

                $orderItemQty = $orderItem->getQtyOrdered();
                $invoiceItemQty = $item->getQty();

                if ($baseOrderItemCustomercreditDiscount && $orderItemQty) {
                    $totalBaseDiscountAmount += $invoice->roundPrice(
                        $baseOrderItemCustomercreditDiscount / $orderItemQty * $invoiceItemQty,
                        'base',
                        false
                    );
                    $totalDiscountAmount += $invoice->roundPrice(
                        $orderItemCustomercreditDiscount / $orderItemQty * $invoiceItemQty,
                        'regular',
                        false
                    );
                }
            }
        }

        $invoice->setBaseCustomercreditDiscount($totalBaseDiscountAmount);
        $invoice->setCustomercreditDiscount($totalDiscountAmount);

        $grandTotal = abs($invoice->getGrandTotal()) < 0.0001 ? 0 : $invoice->getGrandTotal();
        $baseGrandTotal = abs($invoice->getBaseGrandTotal()) < 0.0001 ? 0 : $invoice->getBaseGrandTotal();
        $invoice->setGrandTotal($grandTotal);
        $invoice->setBaseGrandTotal($baseGrandTotal);

        return $this;
    }
}
