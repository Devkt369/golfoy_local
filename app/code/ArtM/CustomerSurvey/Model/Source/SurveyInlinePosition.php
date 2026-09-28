<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\Survey;
use Magento\Framework\Data\OptionSourceInterface;

class SurveyInlinePosition implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Survey::INLINE_POSITION_BEFORE, 'label' => __('Before selector')],
            ['value' => Survey::INLINE_POSITION_AFTER, 'label' => __('After selector')],
            ['value' => Survey::INLINE_POSITION_INSIDE, 'label' => __('Inside selector')],
            ['value' => Survey::INLINE_POSITION_REPLACE, 'label' => __('Replace selector')],
        ];
    }
}