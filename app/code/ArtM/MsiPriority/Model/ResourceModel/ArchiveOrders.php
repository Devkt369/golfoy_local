<?php

namespace ArtM\MsiPriority\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ArchiveOrders extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('vin_inventory_archived_order', 'id');
    }
}
