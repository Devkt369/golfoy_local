<?php

namespace ArtM\TradeIn\Ui\Component\Listing\Column\PaymentMethod;

use Magento\Framework\Data\OptionSourceInterface;

class Options implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('-- Please Select --')],
            ['value' => 'gift-card', 'label' => __('Gift Card')],
            ['value' => 'other', 'label' => __('Other')]
        ];
    }
}
