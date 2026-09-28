<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Customercredit
 * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 *
 */

namespace Magestore\Customercredit\Controller\Checkout;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\Customercredit\Block\Payment\Form;
use Magestore\Customercredit\Helper\Data;
use Magestore\Customercredit\Model\CustomercreditFactory;
use Zend_Json;

/**
 * Class AmountPost
 *
 * Checkout Amount Post controller
 */
class AmountPost extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * @var CustomercreditFactory
     */
    protected $_customercredit;
    /**
     * @var Data
     */
    protected $_customercreditHelper;

    /**
     * AmountPost constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param Cart $cart
     * @param CustomercreditFactory $customercredit
     * @param Data $customercreditHelper
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        Cart $cart,
        CustomercreditFactory $customercredit,
        Data $customercreditHelper
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->_customercredit = $customercredit;
        $this->_customercreditHelper = $customercreditHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $request = $this->getRequest();
        $quote = $this->_checkoutSession->getQuote();
        if ($request->getParam('customer_credit') >= 0 && is_numeric($request->getParam('customer_credit'))) {
            $creditAmount = $request->getParam('customer_credit');
            $baseCreditAmount = $this->_customercreditHelper->getConvertedToBaseCustomerCredit($creditAmount);

            $customer = $this->_customercreditHelper->getCustomer();
            $customerId = $customer->getId();
            $customer_credit = $this->_customercredit->create()->load($customerId, 'customer_id');
            $creditBalance = $customer_credit->getCreditBalance();

            $creditAmount = min($baseCreditAmount, $creditBalance);

            /** integration with gift card and reward points */
            $quote->setCustomerCreditAmount($creditAmount);
            $quote->save();
            return $this->_goBack();
        }

        if (is_numeric($request->getParam('credit_amount')) && $request->getParam('credit_amount') >= 0) {
            $creditAmount = $request->getParam('credit_amount');
            $baseCreditAmount = $this->_customercreditHelper->getConvertedToBaseCustomerCredit($creditAmount);

            $customer = $this->_customercreditHelper->getCustomer();
            $customerId = $customer->getId();
            $customer_credit = $this->_customercredit->create()->load($customerId, 'customer_id');
            $creditBalance = $customer_credit->getCreditBalance();

            $creditAmount = min($baseCreditAmount, $creditBalance);
            $quote->setCustomerCreditAmount($creditAmount);
            $quote->setCreditdiscountAmount($creditAmount);

            $result = $this->_objectManager->create(Form::class)
                ->getCustomercreditData();
            $quote->save();
            $result['credit_discount'] = $quote->getCreditdiscountAmount();
            return $this->getResponse()->setBody(Zend_Json::encode($result));
        }

        if (!$request->getParam('customer_credit') && !$request->getParam('credit_amount')) {
            $quote->setCustomerCreditAmount(0);
            $quote->save();
            return $this->_goBack();
        }

        return $this->_goBack();
    }
}
