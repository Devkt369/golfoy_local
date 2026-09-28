<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Giftvoucher\Observer\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magestore\Giftvoucher\Helper\Data;

/**
 * Giftvoucher - Cart Index Action Observer
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CartIndexActionObserver implements ObserverInterface
{
    protected $checkoutSession;

    protected $helperData;

    protected $messageManager;

    /**
     * CartIndexActionObserver constructor.
     * @param Session $checkoutSession
     * @param Data $helperData
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Session $checkoutSession,
        Data $helperData,
        ManagerInterface $messageManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helperData = $helperData;
        $this->messageManager = $messageManager;
    }
    /**
     * Show Gift Card notification in Cart page
     *
     * @param Observer $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        $session = $this->checkoutSession;
        $quote = $session->getQuote();
        if ($quote->getCouponCode()
            && !$this->helperData->getGeneralConfig('use_with_coupon')
            && ($quote->getGiftVoucherDiscount() > 0)) {
            $this->messageManager->addNoticeMessage(
                __('A coupon code has been used. You cannot apply gift codes with the coupon to get discount.')
            );
            $session->setMessageApplyGiftcardWithCouponCode(false);
        }
        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
    }
}
