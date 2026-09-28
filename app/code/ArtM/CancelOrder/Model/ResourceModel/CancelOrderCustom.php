<?php 

namespace ArtM\CancelOrder\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class CancelOrderCustom extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('cancel_order_custom','id');
    }
}

?>