<?php 

namespace ArtM\Sfmc\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class SfmcOrderFailed extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('sfmc_order_batch_failed_order','id');
    }
}

?>