<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel\SurveyQuestion;

use ArtM\CustomerSurvey\Model\ResourceModel\SurveyQuestion as SurveyQuestionResource;
use ArtM\CustomerSurvey\Model\SurveyQuestion as SurveyQuestionModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(SurveyQuestionModel::class, SurveyQuestionResource::class);
    }
}
