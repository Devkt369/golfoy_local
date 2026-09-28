<?php

namespace ArtM\EstimateDelivery\Plugin;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

class AddEstimatedDatesToOrderGrid
{
    /**
     * Add estimated dates columns to order grid collection
     *
     * @param OrderGridCollection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad(OrderGridCollection $subject, $printQuery = false, $logQuery = false)
    {
        if (!$subject->getFlag('estimated_delivery_joined')) {
            $subject->getSelect()->joinLeft(
                ['sales_order' => $subject->getTable('sales_order')],
                "main_table.entity_id = sales_order.entity_id",
                [
                    'estimated_delivery_early' => 'sales_order.estimated_delivery_early',
                    'estimated_delivery_last' => 'sales_order.estimated_delivery_last'
                ]
            );
            $subject->setFlag('estimated_delivery_joined', true);
        }

        return [$printQuery, $logQuery];
    }
}
