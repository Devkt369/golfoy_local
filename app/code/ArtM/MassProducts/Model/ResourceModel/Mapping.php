<?php
namespace ArtM\MassProducts\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Mapping extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_massupload_mapping', 'mapping_id');
    }
}
