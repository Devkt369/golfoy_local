<?php

namespace ArtM\MsiPriority\Ui\DataProvider\PosOrder\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    /**
     * Override _initSelect to add custom columns
     *
     * @return void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('pos_id', 'main_table.pos_id');
        $this->addFilterToMap('increment_id', 'main_table.increment_id');
        $this->addFilterToMap('created_at', 'main_table.created_at');

        $this->getSelect()->where('main_table.pos_id >= ?', 1);
        
        $this->getSelect()->where(
            'main_table.increment_id COLLATE utf8mb4_unicode_ci NOT IN (SELECT pos_order_id FROM vin_inventory_requests WHERE pos_order_id IS NOT NULL)'
        );

        $this->getSelect()->where(
            'main_table.entity_id NOT IN (
                SELECT order_id FROM vin_inventory_archived_order WHERE order_id IS NOT NULL
            )'
        );
    }
}