<?php

namespace ArtM\CustomerSurvey\Block\Adminhtml\Survey\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveAndContinue extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData()
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'customersurvey_survey_form.customersurvey_survey_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    ['back' => 'edit'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort_order' => 90,
        ];
    }
}
