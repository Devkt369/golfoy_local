<?php
namespace ArtM\SFMC\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use ArtM\SFMC\Model\SfmcCouponSyncFactory;

class Coupon extends Template
{
    protected $SfmcCouponSync;

    public function __construct(
        Context $context,
        SfmcCouponSyncFactory $SfmcCouponSync,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->SfmcCouponSync = $SfmcCouponSync;
    }

    public function getCouponData()
    {
        $coupon= $this->SfmcCouponSync->create();
        $coupon = $coupon->getCollection();
        return $coupon->getData();
    }
}
