<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SurveyLocation extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_customersurvey_survey_location', 'entity_id');
    }
}
