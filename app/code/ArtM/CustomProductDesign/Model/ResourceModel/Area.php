<?php
namespace ArtM\CustomProductDesign\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Area extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_customprint_area', 'area_id');
    }
}
