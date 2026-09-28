<?php

namespace ArtM\PrintPriceTracking\Model\ResourceModel\Report;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ArtM\PrintPriceTracking\Model\Report as ReportModel;
use ArtM\PrintPriceTracking\Model\ResourceModel\Report as ReportResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            ReportModel::class,
            ReportResource::class
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->join(
            ['order_table' => $this->getTable('sales_order')],
            'main_table.order_id = order_table.entity_id',
            [
                'increment_id',
                'status',
                'created_at',
                new \Zend_Db_Expr('main_table.print_price * main_table.qty_ordered AS print_total')
            ]
        );

        // Map fields to avoid ambiguity
        $this->addFilterToMap('created_at', 'order_table.created_at');
        $this->addFilterToMap('increment_id', 'order_table.increment_id');
        $this->addFilterToMap('status', 'order_table.status');
        $this->addFilterToMap('print_total', 'print_total');

        // Note: do not call addFieldToMap here because the collection interceptor
        // may not implement it. Field mapping for filters is handled above.

        return $this;
    }
}