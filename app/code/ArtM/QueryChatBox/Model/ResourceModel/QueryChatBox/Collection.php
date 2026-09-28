<?php
namespace ArtM\QueryChatBox\Model\ResourceModel\QueryChatBox;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\QueryChatBox\Model\QueryChatBox', 'ArtM\QueryChatBox\Model\ResourceModel\QueryChatBox');
    }
}