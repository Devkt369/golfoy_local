<?php

namespace ArtM\CustomerSurvey\Block\Adminhtml\Survey\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Delete extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData()
    {
        $data = [];
        $surveyId = $this->getSurveyId();
        if ($surveyId) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => "deleteConfirm('"
                    . __('Are you sure you want to delete this survey journey?')
                    . "', '"
                    . $this->getUrl('customersurvey/survey/delete', ['survey_id' => $surveyId])
                    . "')",
                'sort_order' => 30,
            ];
        }

        return $data;
    }
}
