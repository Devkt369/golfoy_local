<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\Survey;
use Magento\Framework\Data\OptionSourceInterface;

class SurveyAnsweredFrequency implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Survey::FREQ_ANSWERED_NEVER, 'label' => __('Never show again')],
            ['value' => Survey::FREQ_ANSWERED_AFTER_DAYS, 'label' => __('Show again after X days')],
        ];
    }
}