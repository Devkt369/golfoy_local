<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel\QuestionOption;

use ArtM\CustomerSurvey\Model\QuestionOption as OptionModel;
use ArtM\CustomerSurvey\Model\ResourceModel\QuestionOption as OptionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(OptionModel::class, OptionResource::class);
    }
}
