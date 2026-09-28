<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcCouponSync;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcCouponSync', 'ArtM\SFMC\Model\ResourceModel\SfmcCouponSync');
    }
}