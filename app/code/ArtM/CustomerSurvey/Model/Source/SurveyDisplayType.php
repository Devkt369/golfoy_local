<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\Survey;
use Magento\Framework\Data\OptionSourceInterface;

class SurveyDisplayType implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Survey::DISPLAY_TYPE_INLINE, 'label' => __('Inline')],
            ['value' => Survey::DISPLAY_TYPE_POPUP, 'label' => __('Popup')],
        ];
    }
}