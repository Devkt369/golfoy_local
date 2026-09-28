<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcAbandonedCoupon;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcAbandonedCoupon', 'ArtM\SFMC\Model\ResourceModel\SfmcAbandonedCoupon');
    }
}