<?php
namespace ArtM\PrintPriceTracking\Plugin\Quote\Item;

class ToOrderItemPlugin
{
    public function afterConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Magento\Sales\Model\Order\Item $result,
        \Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem
    ) {
        $result->setPrintPrice($quoteItem->getPrintPrice() ?: 0);
        return $result;
    }
}