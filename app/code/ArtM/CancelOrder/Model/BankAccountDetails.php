<?php 

namespace ArtM\CancelOrder\Model;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BankAccountDetails extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\CancelOrder\Model\ResourceModel\BankAccountDetails');
    }
}

