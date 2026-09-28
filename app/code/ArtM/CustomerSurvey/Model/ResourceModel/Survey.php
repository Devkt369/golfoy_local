<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Survey extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_customersurvey_survey', 'survey_id');
    }
}
