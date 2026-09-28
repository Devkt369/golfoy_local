<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcAbandonedCart;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcAbandonedCart', 'ArtM\SFMC\Model\ResourceModel\SfmcAbandonedCart');
    }
}