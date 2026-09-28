<?php

namespace ArtM\TradeIn\Ui\Component\Listing\Column\Listed;

use Magento\Framework\Data\OptionSourceInterface;

class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [
                ['value' => '1', 'label' => __('Listed')],
                ['value' => '0', 'label' => __('Not Listed')],
            ];
        }

        return $this->options;
    }
}
