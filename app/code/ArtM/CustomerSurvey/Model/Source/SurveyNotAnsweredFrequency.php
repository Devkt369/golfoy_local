<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\Survey;
use Magento\Framework\Data\OptionSourceInterface;

class SurveyNotAnsweredFrequency implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Survey::FREQ_NOT_ANSWERED_EVERY_VISIT, 'label' => __('Show every eligible visit')],
            ['value' => Survey::FREQ_NOT_ANSWERED_ONCE_NEVER, 'label' => __('Show once and never show again')],
            ['value' => Survey::FREQ_NOT_ANSWERED_AFTER_DAYS, 'label' => __('Show again after X days')],
        ];
    }
}