<?php

namespace ArtM\CustomerSurvey\Block\Adminhtml\Survey\Edit\Button;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getSurveyId(): ?int
    {
        $id = (int)$this->context->getRequest()->getParam('survey_id');
        return $id ?: null;
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
