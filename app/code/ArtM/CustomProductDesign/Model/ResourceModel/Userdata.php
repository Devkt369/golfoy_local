<?php
namespace ArtM\CustomProductDesign\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Userdata extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_customprint_userdata', 'id');
    }
}
