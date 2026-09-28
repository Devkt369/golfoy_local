<?php
namespace ArtM\AdminAI\Model\ResourceModel\ChatMessage;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\AdminAI\Model\ChatMessage::class,
            \ArtM\AdminAI\Model\ResourceModel\ChatMessage::class
        );
    }
}
