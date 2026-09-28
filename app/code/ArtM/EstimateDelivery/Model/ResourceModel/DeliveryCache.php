<?php

namespace ArtM\EstimateDelivery\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DeliveryCache extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_delivery_estimation_cache', 'cache_id');
    }

    /**
     * Get cache data by product ID
     */
    public function getCacheByProductId($productId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('product_id = ?', $productId)
            ->where('is_valid = ?', 1);

        return $connection->fetchRow($select);
    }

    /**
     * Get cache data by SKU
     */
    public function getCacheBySku($sku)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('sku = ?', $sku)
            ->where('is_valid = ?', 1);

        return $connection->fetchRow($select);
    }

    /**
     * Get multiple cache records by product IDs
     */
    public function getCacheByProductIds(array $productIds)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('product_id IN (?)', $productIds);

        return $connection->fetchAll($select);
    }

    /**
     * Get expired cache records (older than 2 days)
     */
    public function getExpiredCache()
    {
        $connection = $this->getConnection();
        $twoDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));
        
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('calculated_at < ?', $twoDaysAgo)
            ->where('is_valid = ?', 1);

        return $connection->fetchAll($select);
    }

    /**
     * Invalidate cache for product
     */
    public function invalidateCacheForProduct($productId)
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getMainTable(),
            ['is_valid' => 0],
            ['product_id = ?' => $productId]
        );
    }

    /**
     * Delete cache entries for product to avoid duplicates
     */
    public function deleteByProductId($productId)
    {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getMainTable(),
            ['product_id = ?' => $productId]
        );
    }
}