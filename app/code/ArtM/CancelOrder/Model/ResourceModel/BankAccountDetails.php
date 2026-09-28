<?php 

namespace ArtM\CancelOrder\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class BankAccountDetails extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_refund_accounts','id');
    }
}

?>