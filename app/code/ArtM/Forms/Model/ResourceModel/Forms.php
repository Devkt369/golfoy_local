<?php
namespace ArtM\Forms\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Forms extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_forms', 'id');
    }
}
