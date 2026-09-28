<?php

namespace ArtM\ShipmentTracking\Model\ResourceModel\Order\Shipment\Grid;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Shipment\Grid\Collection
{
    protected function _initSelect()
    {
        parent::_initSelect();

        $trackTable = $this->getTable('sales_shipment_track');

        $this->getSelect()->joinLeft(
            ['sst' => $trackTable],
            'main_table.entity_id = sst.parent_id',
            []
        );

        $this->getSelect()->columns([
            'courier_partner' => new \Zend_Db_Expr(
                "GROUP_CONCAT(DISTINCT IFNULL(sst.title,'N/A') SEPARATOR '\n')"
            ),
            'awb_no' => new \Zend_Db_Expr(
                "GROUP_CONCAT(DISTINCT sst.track_number SEPARATOR '\n')"
            ),
            'tracking_status' => new \Zend_Db_Expr(
                "GROUP_CONCAT(DISTINCT IFNULL(sst.tracking_status,'N/A') SEPARATOR '\n')"
            )
        ]);

        $this->getSelect()->group('main_table.entity_id');

        // Fix ambiguous column issue during filtering/sorting/export
        $this->addFilterToMap('entity_id',         'main_table.entity_id');
        $this->addFilterToMap('order_created_at',  'main_table.order_created_at');
        $this->addFilterToMap('created_at',        'main_table.created_at');
        $this->addFilterToMap('updated_at',        'main_table.updated_at');
        $this->addFilterToMap('order_id',          'main_table.order_id');
        $this->addFilterToMap('store_id',          'main_table.store_id');
        $this->addFilterToMap('customer_name',     'main_table.customer_name');
        $this->addFilterToMap('shipping_name',     'main_table.shipping_name');
        $this->addFilterToMap('total_qty',         'main_table.total_qty');
        
        // Add mapping for new virtual columns if you want them filterable
        $this->addFilterToMap('courier_partner',   'sst.title');
        $this->addFilterToMap('awb_no',            'sst.track_number');
        $this->addFilterToMap('tracking_status',   'sst.tracking_status');

        return $this;
    }
}