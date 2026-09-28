<?php 

namespace ArtM\Sfmc\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class SfmcCustomerFailed extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('sfmc_customer_batch_failed_customer','id');
    }
}

?>