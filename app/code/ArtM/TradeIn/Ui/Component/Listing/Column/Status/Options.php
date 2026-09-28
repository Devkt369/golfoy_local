<?php

namespace ArtM\TradeIn\Ui\Component\Listing\Column\Status;

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
            ['value' => 'Posted', 'label' => __('Posted')],
            ['value' => 'Reviewing', 'label' => __('Reviewing')],
            ['value' => 'Answered', 'label' => __('Answered')],
            ['value' => 'rejected', 'label' => __('Rejected')]
        ];
    }
}
