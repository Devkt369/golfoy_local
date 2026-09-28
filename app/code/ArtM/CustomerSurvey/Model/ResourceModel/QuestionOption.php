<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QuestionOption extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_customersurvey_question_option', 'option_id');
    }
}
