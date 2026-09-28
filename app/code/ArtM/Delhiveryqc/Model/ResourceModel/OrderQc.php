<?php 

namespace ArtM\Delhiveryqc\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class OrderQc extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_delhiveryqc_order','id');
    }
}

?>