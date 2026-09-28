<?php
namespace ArtM\Optimization\Model\ResourceModel\ProductCache;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ArtM\Optimization\Model\ProductCache;
use ArtM\Optimization\Model\ResourceModel\ProductCache as ProductCacheResource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(ProductCache::class, ProductCacheResource::class);
    }
}
