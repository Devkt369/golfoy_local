<?php

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\Survey;
use Magento\Framework\Data\OptionSourceInterface;

class SurveyStatus implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Survey::STATUS_DRAFT, 'label' => __('Draft')],
            ['value' => Survey::STATUS_ACTIVE, 'label' => __('Active')],
            ['value' => Survey::STATUS_DISABLED, 'label' => __('Disabled')],
        ];
    }
}
