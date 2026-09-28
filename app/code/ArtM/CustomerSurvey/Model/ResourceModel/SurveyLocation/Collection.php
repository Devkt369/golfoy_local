<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel\SurveyLocation;

use ArtM\CustomerSurvey\Model\ResourceModel\SurveyLocation as SurveyLocationResource;
use ArtM\CustomerSurvey\Model\SurveyLocation as SurveyLocationModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(SurveyLocationModel::class, SurveyLocationResource::class);
    }
}
