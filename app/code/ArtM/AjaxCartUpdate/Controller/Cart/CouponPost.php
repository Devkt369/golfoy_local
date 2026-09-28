<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ArtM\AjaxCartUpdate\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CouponPost extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;
    protected $resultJsonFactory;
    protected $scopeConfig;

     /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @codeCoverageIgnore
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->scopeConfig = $scopeConfig;
        $this->couponFactory = $couponFactory;
        $this->quoteRepository = $quoteRepository;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Initialize coupon
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $isRemove   = (int)$this->getRequest()->getParam('remove_coupon') === 1;
        $couponCode = $isRemove
            ? ''
            : trim($this->getRequest()->getParam('coupon_code', ''));

        $cartQuote     = $this->cart->getQuote();
        $oldCouponCode = $cartQuote->getCouponCode() ?? '';
        $codeLength    = strlen($couponCode);

        // ✅ If AJAX mode is OFF — do all logic then redirect, never return JSON
        if (!$this->getAjaxCartStatus()) {
            if (!$codeLength && !strlen($oldCouponCode)) {
                return $this->_goBack();
            }

            try {
                $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;
                $itemsCount        = $cartQuote->getItemsCount();

                if ($itemsCount) {
                    $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                    $cartQuote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                    $this->quoteRepository->save($cartQuote);
                }

                if ($isRemove || !$codeLength) {
                    $this->messageManager->addSuccessMessage(__('You canceled the coupon code.'));
                } elseif ($codeLength) {
                    $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);
                    $coupon  = $this->couponFactory->create();
                    $coupon->load($couponCode, 'code');

                    if ($isCodeLengthValid && $coupon->getId() && (!$itemsCount || $couponCode == $cartQuote->getCouponCode())) {
                        if (!$itemsCount) {
                            $this->_checkoutSession->getQuote()->setCouponCode($couponCode)->save();
                        }
                        $this->messageManager->addSuccessMessage(
                            __('You used coupon code "%1".', $escaper->escapeHtml($couponCode))
                        );
                    } else {
                        $this->messageManager->addErrorMessage(
                            __('The coupon code "%1" is not valid.', $escaper->escapeHtml($couponCode))
                        );
                    }
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We cannot apply the coupon code.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }

            // ✅ Always redirect — never JSON — when AJAX mode is OFF
            return $this->_goBack();
        }

        // ✅ AJAX mode is ON — return JSON responses below
        try {
            $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;
            $itemsCount        = $cartQuote->getItemsCount();

            if ($itemsCount) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                $this->quoteRepository->save($cartQuote);
            }

            if ($isRemove || !$codeLength) {
                $this->messageManager->addSuccessMessage(__('You canceled the coupon code.'));
                return $result->setData(['success' => true, 'removed' => true]);
            }

            $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);
            $coupon  = $this->couponFactory->create();
            $coupon->load($couponCode, 'code');

            if (!$itemsCount) {
                if ($isCodeLengthValid && $coupon->getId()) {
                    $this->_checkoutSession->getQuote()->setCouponCode($couponCode)->save();
                    $this->messageManager->addSuccessMessage(
                        __('You used coupon code "%1".', $escaper->escapeHtml($couponCode))
                    );
                    return $result->setData(['success' => true, 'removed' => false]);
                } else {
                    $this->messageManager->addErrorMessage(
                        __('The coupon code "%1" is not valid.', $escaper->escapeHtml($couponCode))
                    );
                    return $result->setData(['success' => false, 'removed' => false]);
                }
            } else {
                if ($isCodeLengthValid && $coupon->getId() && $couponCode == $cartQuote->getCouponCode()) {
                    $this->messageManager->addSuccessMessage(
                        __('You used coupon code "%1".', $escaper->escapeHtml($couponCode))
                    );
                    return $result->setData(['success' => true, 'removed' => false]);
                } else {
                    $this->messageManager->addErrorMessage(
                        __('The coupon code "%1" is not valid.', $escaper->escapeHtml($couponCode))
                    );
                    return $result->setData(['success' => false, 'removed' => false]);
                }
            }

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $result->setData(['success' => false, 'removed' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('We cannot apply the coupon code.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $result->setData(['success' => false, 'removed' => false, 'message' => __('We cannot apply the coupon code.')]);
        }
    }

    private function getAjaxCartStatus()
    {
        return $this->scopeConfig->getValue(
            'ajaxcart/module_status/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
