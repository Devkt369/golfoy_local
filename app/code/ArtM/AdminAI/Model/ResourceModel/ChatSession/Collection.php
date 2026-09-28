<?php
namespace ArtM\AdminAI\Model\ResourceModel\ChatSession;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\AdminAI\Model\ChatSession::class,
            \ArtM\AdminAI\Model\ResourceModel\ChatSession::class
        );
    }
}
