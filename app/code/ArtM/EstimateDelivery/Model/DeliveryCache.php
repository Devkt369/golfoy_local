<?php

namespace ArtM\EstimateDelivery\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class DeliveryCache extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'artm_delivery_cache';

    protected $_cacheTag = 'artm_delivery_cache';
    protected $_eventPrefix = 'artm_delivery_cache';

    protected function _construct()
    {
        $this->_init(\ArtM\EstimateDelivery\Model\ResourceModel\DeliveryCache::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }

    /**
     * Check if cache is valid (less than 2 days old)
     */
    public function isCacheValid()
    {
        if (!$this->getCalculatedAt()) {
            return false;
        }

        $calculatedAt = new \DateTime($this->getCalculatedAt());
        $now = new \DateTime();
        $diff = $now->diff($calculatedAt);

        return $diff->days < 2;
    }

    /**
     * Get simple product IDs as array (for configurable products)
     */
    public function getSimpleProductIdsArray()
    {
        $ids = $this->getSimpleProductIds();
        return $ids ? json_decode($ids, true) : [];
    }

    /**
     * Set simple product IDs as JSON (for configurable products)
     */
    public function setSimpleProductIdsArray(array $ids)
    {
        $this->setSimpleProductIds(json_encode($ids));
        return $this;
    }
}