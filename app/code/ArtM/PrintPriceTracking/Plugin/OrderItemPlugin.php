<?php

namespace ArtM\PrintPriceTracking\Plugin;

class OrderItemPlugin
{
    public function aroundGetPrice(
        \Magento\Sales\Model\Order\Item $subject,
        callable $proceed,
        $currency = null
    ) {
        $price = $proceed($currency);
        $printAmount = $this->getPrintAmountFromOrderItem($subject);
        if ($printAmount > 0) {
            return $price + $printAmount;
        }

        return $price;
    }

    private function getPrintAmountFromOrderItem(\Magento\Sales\Model\Order\Item $item)
    {
        $options = $item->getProductOptions();
        if (empty($options['options'])) {
            return 0;
        }

        $printAmount = 0;
        foreach ($options['options'] as $option) {
            if (!isset($option['print_value']) && !isset($option['value'])) {
                continue;
            }

            $optionValue = isset($option['print_value']) ? $option['print_value'] : $option['value'];
            if (is_numeric($optionValue)) {
                $printAmount += (float)$optionValue;
            }
        }

        return round($printAmount, 4);
    }
}
