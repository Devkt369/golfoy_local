<?php 

namespace ArtM\QueryChatBox\Model;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QueryChatBox extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\QueryChatBox\Model\ResourceModel\QueryChatBox');
    }
}

