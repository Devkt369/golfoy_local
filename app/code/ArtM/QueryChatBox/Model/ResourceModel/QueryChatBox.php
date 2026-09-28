<?php 

namespace ArtM\QueryChatBox\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class QueryChatBox extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_querychatbox','id');
    }
}
