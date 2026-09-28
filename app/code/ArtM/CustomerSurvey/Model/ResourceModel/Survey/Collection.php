<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel\Survey;

use ArtM\CustomerSurvey\Model\ResourceModel\Survey as SurveyResource;
use ArtM\CustomerSurvey\Model\Survey as SurveyModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(SurveyModel::class, SurveyResource::class);
    }
}
