<?php 
namespace ArtM\Delhiveryqc\Model;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OrderQc extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\Delhiveryqc\Model\ResourceModel\OrderQc');
    }

}