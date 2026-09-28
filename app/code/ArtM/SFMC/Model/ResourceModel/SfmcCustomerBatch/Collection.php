<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcCustomerBatch;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcCustomerBatch', 'ArtM\SFMC\Model\ResourceModel\SfmcCustomerBatch');
    }
}