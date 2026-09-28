<?php 

namespace ArtM\Sfmc\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class SfmcOrderSyncSucceed extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('sfmc_success_order','id');
    }
}

?>