<?php

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\Location;
use Magento\Framework\Data\OptionSourceInterface;

class Position implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Location::POSITION_BEFORE, 'label' => __('Before Selector')],
            ['value' => Location::POSITION_AFTER, 'label' => __('After Selector')],
            ['value' => Location::POSITION_INSIDE, 'label' => __('Inside Selector')],
            ['value' => Location::POSITION_REPLACE, 'label' => __('Replace Selector')],
        ];
    }
}
