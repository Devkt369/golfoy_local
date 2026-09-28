<?php
namespace ArtM\AdminAI\Model\ResourceModel\QueryCache;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\ArtM\AdminAI\Model\QueryCache::class, \ArtM\AdminAI\Model\ResourceModel\QueryCache::class);
    }
}
