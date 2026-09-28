<?php

namespace ArtM\MsiPriority\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class VinRequest extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('vin_inventory_requests', 'id');
    }
}
