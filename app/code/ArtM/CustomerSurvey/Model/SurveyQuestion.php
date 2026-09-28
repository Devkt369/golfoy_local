<?php

namespace ArtM\CustomerSurvey\Model;

use Magento\Framework\Model\AbstractModel;

class SurveyQuestion extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\CustomerSurvey\Model\ResourceModel\SurveyQuestion::class);
    }
}
