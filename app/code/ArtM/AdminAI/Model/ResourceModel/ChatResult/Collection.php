<?php
namespace ArtM\AdminAI\Model\ResourceModel\ChatResult;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\ArtM\AdminAI\Model\ChatResult::class, \ArtM\AdminAI\Model\ResourceModel\ChatResult::class);
    }
}
