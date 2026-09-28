<?php
namespace Shipsy\EcommerceExtension\Model\Plugin\Sales\Order;
 
class Grid
{
    public function afterSearch($intercepter, $collection) {
        if ($collection->getMainTable() === $collection->getConnection()->getTableName('sales_order_grid')) {
 
            $leftJoinTableName = $collection->getConnection()->getTableName('sales_order');
 
            $collection
                ->getSelect()
                ->joinLeft(
                    ['so'=>$leftJoinTableName],
                    "so.entity_id = main_table.entity_id",
                    [
                        'shipsy_reference_numbers' => 'so.shipsy_reference_numbers',
                        'shipsy_tracking_url' => 'so.shipsy_tracking_url',
                    ]
                );
 
            $where = $collection->getSelect()->getPart(\Magento\Framework\DB\Select::WHERE);
            //-----------------fix purchase filter is not working in sales order grid-------------
            foreach($where as $k => $v){
                if (str_contains($v, '(`created_at`')) { 
                       $nv = str_replace('(`created_at`' ,'(main_table.`created_at`', $v );
                    $where[$k] = $nv;
                }
            }
            //-------------------------------
            $collection->getSelect()->setPart(\Magento\Framework\DB\Select::WHERE, $where);            
 
 
        }
        return $collection;
    }
}