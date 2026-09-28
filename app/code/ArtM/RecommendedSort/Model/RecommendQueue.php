<?php 

namespace ArtM\RecommendedSort\Model;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RecommendQueue extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\RecommendedSort\Model\ResourceModel\RecommendQueue');
    }
}
