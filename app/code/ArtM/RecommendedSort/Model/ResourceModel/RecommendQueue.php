<?php 

namespace ArtM\RecommendedSort\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class RecommendQueue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_recommendedsort_queue','id');
    }
}

?>