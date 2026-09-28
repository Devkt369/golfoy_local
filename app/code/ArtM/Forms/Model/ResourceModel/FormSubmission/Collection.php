<?php
namespace ArtM\Forms\Model\ResourceModel\FormSubmission;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('ArtM\Forms\Model\FormSubmission', 'ArtM\Forms\Model\ResourceModel\FormSubmission');
    }
}
