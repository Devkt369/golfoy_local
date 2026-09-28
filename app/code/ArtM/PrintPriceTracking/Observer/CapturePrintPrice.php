<?php
namespace ArtM\PrintPriceTracking\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CapturePrintPrice implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getItem();
        $product = $quoteItem->getProduct();

        if (!$product) {
            return $this;
        }

        $printPrice = (float) ($product->getData('print_price') ?: 0);
        if ($printPrice <= 0) {
            return $this;
        }

        $buyRequest = $quoteItem->getBuyRequest();
        $isPrintSelected = false;

        if ($buyRequest) {
            $printFlags = [
                'print_option',
                'can_print',
                'custom_print',
                'print_enabled',
                'print',
                'is_print'
            ];

            foreach ($printFlags as $flag) {
                if ($buyRequest->getData($flag)) {
                    $isPrintSelected = true;
                    break;
                }
            }
        }

        if (!$isPrintSelected && $product->getData('can_print')) {
            $isPrintSelected = true;
        }

        if ($isPrintSelected) {
            $quoteItem->setData('print_price', $printPrice);
        }

        return $this;
    }
}