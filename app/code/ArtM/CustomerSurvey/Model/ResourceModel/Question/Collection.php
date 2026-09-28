<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel\Question;

use ArtM\CustomerSurvey\Model\Question as QuestionModel;
use ArtM\CustomerSurvey\Model\ResourceModel\Question as QuestionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(QuestionModel::class, QuestionResource::class);
    }
}
