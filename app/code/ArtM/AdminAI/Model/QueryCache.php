<?php
namespace ArtM\AdminAI\Model;

use Magento\Framework\Model\AbstractModel;

class QueryCache extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\AdminAI\Model\ResourceModel\QueryCache::class);
    }
}
