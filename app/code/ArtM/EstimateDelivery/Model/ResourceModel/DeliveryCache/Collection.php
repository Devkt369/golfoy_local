<?php

namespace ArtM\EstimateDelivery\Model\ResourceModel\DeliveryCache;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'cache_id';
    protected $_eventPrefix = 'artm_delivery_cache_collection';
    protected $_eventObject = 'delivery_cache_collection';

    protected function _construct()
    {
        $this->_init(
            \ArtM\EstimateDelivery\Model\DeliveryCache::class,
            \ArtM\EstimateDelivery\Model\ResourceModel\DeliveryCache::class
        );
    }

    /**
     * Filter by valid cache only
     */
    public function addValidFilter()
    {
        return $this->addFieldToFilter('is_valid', 1);
    }

    /**
     * Filter by product type
     */
    public function addProductTypeFilter($type)
    {
        return $this->addFieldToFilter('product_type', $type);
    }

    /**
     * Filter by fresh cache (less than 2 days old)
     */
    public function addFreshFilter()
    {
        $twoDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));
        return $this->addFieldToFilter('calculated_at', ['gt' => $twoDaysAgo]);
    }

    /**
     * Filter by expired cache (older than 2 days)
     */
    public function addExpiredFilter()
    {
        $twoDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));
        return $this->addFieldToFilter('calculated_at', ['lt' => $twoDaysAgo]);
    }
}