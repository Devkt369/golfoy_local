<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SurveyQuestion extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_customersurvey_survey_question', 'entity_id');
    }
}
