<?php
namespace ArtM\Forms\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class FormSubmission extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_form_submissions', 'submission_id');
    }
}
