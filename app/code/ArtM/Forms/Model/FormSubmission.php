<?php
namespace ArtM\Forms\Model;

use Magento\Framework\Model\AbstractModel;

class FormSubmission extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('ArtM\Forms\Model\ResourceModel\FormSubmission');
    }
}
