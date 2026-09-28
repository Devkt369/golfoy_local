<?php 

namespace ArtM\Sfmc\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class SfmcCouponSync extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('sfmc_coupon_status','id');
    }
}

?>