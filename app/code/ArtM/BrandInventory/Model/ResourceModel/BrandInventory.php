<?php

namespace ArtM\BrandInventory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BrandInventory extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_brand_inventory', 'id'); 
        // table name, primary key
    }
}