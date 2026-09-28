<?php

namespace ArtM\PrintPriceTracking\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Report extends AbstractDb
{
    protected function _construct()
    {
        // Main table
        $this->_init('sales_order_item', 'item_id');
    }
}