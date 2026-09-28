<?php
namespace ArtM\AdminAI\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QueryCache extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_adminai_query_cache', 'cache_id');
    }
}
