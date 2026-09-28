<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcCustomerFailed;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcCustomerFailed', 'ArtM\SFMC\Model\ResourceModel\SfmcCustomerFailed');
    }
}