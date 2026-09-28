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

namespace Magestore\Customercredit\Block\Adminhtml\Order;

use Magento\Backend\Block\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\Customercredit\Helper\Account;
use Magestore\Customercredit\Helper\Data;
use Magestore\Customercredit\Model\CustomercreditFactory;
use Magento\Backend\Model\Session\Quote as QuoteSession;

/**
 * Form apply store credit
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Form extends AbstractCreate
{
    /**
     * @var CurrencyFactory
     */
    protected $_currencyFactory;
    /**
     * @var CurrencyInterface
     */
    protected $_localeCurrency;
    /**
     * @var Data
     */
    protected $_creditHelper;
    /**
     * @var Account
     */
    protected $_accountHelper;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var CustomercreditFactory
     */
    protected $_creditModel;
    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Form constructor.
     *
     * @param Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param CurrencyFactory $currencyFactory
     * @param CurrencyInterface $localeCurrency
     * @param Data $creditHelper
     * @param Account $creditaccountHelper
     * @param CustomercreditFactory $creditModel
     * @param Session $checkoutSession
     * @param ObjectManagerInterface $objectManager
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        CurrencyFactory $currencyFactory,
        CurrencyInterface $localeCurrency,
        Data $creditHelper,
        Account $creditaccountHelper,
        CustomercreditFactory $creditModel,
        Session $checkoutSession,
        ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->_currencyFactory = $currencyFactory;
        $this->_localeCurrency = $localeCurrency;
        $this->_creditHelper = $creditHelper;
        $this->_accountHelper = $creditaccountHelper;
        $this->_storeManager = $context->getStoreManager();
        $this->_creditModel = $creditModel;
        $this->_checkoutSession = $checkoutSession;
        $this->_objectManager = $objectManager;
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $data);
    }

    /**
     * Get credit helper
     *
     * @return Data
     */
    public function getCreditHelper()
    {
        return $this->_creditHelper;
    }

    /**
     * Get account helper
     *
     * @return Account
     */
    public function getAccountHelper()
    {
        return $this->_accountHelper;
    }

    /**
     * Get Default Customer Credit
     *
     * @return float
     */
    public function getDefaultCustomerCredit()
    {
        $customer_id = $this->_sessionQuote->getCustomerId();
        $credit = $this->_creditModel->create()->load($customer_id, 'customer_id')->getCreditBalance();
        return $credit;
    }

    /**
     * Get Applied Credit Amount
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAppliedCreditAmount()
    {
        /** @var Quote $quote */
        $quote = $this->_objectManager->get(QuoteSession::class)->getQuote();
        if ($quote->getCustomerCreditAmountEntered()) {
            $creditAmount = $quote->getCreditdiscountAmount();
        } else {
            $creditAmount = $quote->getCustomerCreditAmount();
        }
        return $creditAmount;
    }

    /**
     * Get Customer Credit
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerCredit()
    {
        $credit = $this->getDefaultCustomerCredit();
        if ($amount = $this->getAppliedCreditAmount()) {
            $credit -= $amount;
        }
        return $credit;
    }

    /**
     * Is Assign Credit
     *
     * @return bool
     */
    public function isAssignCredit()
    {
        return true;
    }

    /**
     * Has Customer Credit Item
     *
     * @return bool
     */
    public function hasCustomerCreditItem()
    {
        $quote = $this->_sessionQuote->getQuote();
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProduct()->getTypeId() == 'customercredit') {
                return true;
            }
        }
        return false;
    }

    /**
     * Has Customer Credit Item Only
     *
     * @return bool
     */
    public function hasCustomerCreditItemOnly()
    {
        $quote = $this->_sessionQuote->getQuote();
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProduct()->getTypeId() != 'customercredit') {
                return false;
            }
        }
        return true;
    }
}
