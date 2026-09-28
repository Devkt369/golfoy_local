<?php
namespace ArtM\CancelOrder\Model\ResourceModel\BankAccountDetails;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\CancelOrder\Model\BankAccountDetails', 'ArtM\CancelOrder\Model\ResourceModel\BankAccountDetails');
    }
}