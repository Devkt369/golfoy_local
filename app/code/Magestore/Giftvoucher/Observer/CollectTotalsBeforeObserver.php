<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Giftvoucher\Observer;

/**
 * Giftvoucher - CollectTotalsBeforeObserver
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CollectTotalsBeforeObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $session;

    protected $helperData;

    /**
     * CollectTotalsBeforeObserver constructor.
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magestore\Giftvoucher\Helper\Data $helperData
     */
    public function __construct(
        \Magento\Checkout\Model\Session $session,
        \Magestore\Giftvoucher\Helper\Data $helperData
    ) {
        $this->session = $session;
        $this->helperData = $helperData;
    }

    /**
     * Set Quote information about Gift Card discount
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $session = $this->session;
        $quote = $observer->getEvent()->getQuote();
        $quote->setBaseGiftVoucherDiscount(0);
        $quote->setGiftVoucherDiscount(0);
        $quote->setMagestoreBaseDiscount(0);
        $quote->setMagestoreDiscount(0);
        $quote->setBaseGiftvoucherDiscountForShipping(0);
        $quote->setGiftvoucherDiscountForShipping(0);
        $quote->setMagestoreBaseDiscountForShipping(0);
        $quote->setMagestoreDiscountForShipping(0);
        $quote->setGiftcodesAppliedDiscountForShipping(null);

        if ($quote->getCouponCode() && !$this->helperData->getGeneralConfig('use_with_coupon')
            && ($quote->getUseGiftCreditAmount() > 0 || $quote->getGiftVoucherDiscount() > 0)
        ) {
            if ($quote->getUseGiftCard()) {
                $quote->setUseGiftCard(null)
                    ->setGiftCodes(null)
                    ->setBaseAmountUsed(null)
                    ->setBaseGiftVoucherDiscount(null)
                    ->setGiftVoucherDiscount(null)
                    ->setCodesBaseDiscount(null)
                    ->setCodesDiscount(null)
                    ->setGiftMaxUseAmount(null);
            }
            if ($quote->getUseGiftCardCredit()) {
                $quote->setUseGiftCardCredit(null)
                    ->setMaxCreditUsed(null)
                    ->setBaseUseGiftCreditAmount(null)
                    ->setUseGiftCreditAmount(null);
            }

            $session->setMessageApplyGiftcardWithCouponCode(true);
        }

        if ($codes = $quote->getGiftCodes()) {
            $codesArray = array_unique(explode(',', (string)$codes));
            foreach (array_keys($codesArray) as $key) {
                $codesArray[$key] = 0;
            }
            $quote->setBaseAmountUsed(implode(',', $codesArray));
        } else {
            $quote->setBaseAmountUsed(null);
        }
        $quote->setBaseGiftVoucherDiscount(0);
        $quote->setGiftVoucherDiscount(0);
        $quote->setUseGiftCreditAmount(0);

        foreach ($quote->getAllAddresses() as $address) {
            $address->setGiftcardCreditAmount(0);
            $address->setBaseUseGiftCreditAmount(0);
            $address->setUseGiftCreditAmount(0);
            $address->setBaseGiftVoucherDiscount(0);
            $address->setGiftVoucherDiscount(0);
            $address->setGiftvoucherBaseHiddenTaxAmount(0);
            $address->setGiftvoucherHiddenTaxAmount(0);
            $address->setBaseGiftvoucherDiscountForShipping(0);
            $address->setGiftvoucherDiscountForShipping(0);
            $address->setMagestoreBaseDiscount(0);
            $address->setMagestoreDiscount(0);
            $address->setMagestoreBaseDiscountForShipping(0);
            $address->setMagestoreDiscountForShipping(0);
            $address->setGiftcodesAppliedDiscountForShipping(null);
            $address->setShippingAmountForDiscount(null);
            $address->setBaseShippingAmountForDiscount(null);

            foreach ($address->getAllItems() as $item) {
                $item->setBaseGiftVoucherDiscount(0)
                    ->setGiftVoucherDiscount(0)
                    ->setMagestoreBaseDiscount(0)
                    ->setMagestoreDiscount(0)
                    ->setGiftcodesApplied(null);
            }
        }
    }
}
