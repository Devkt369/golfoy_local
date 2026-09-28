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

namespace Magestore\Customercredit\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magestore\Customercredit\Model\CustomercreditFactory;
use Magestore\Customercredit\Model\TransactionFactory;

/**
 * Event handle order & quote when converting quote to order
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ConvertQuoteToOrder implements ObserverInterface
{
    /**
     * @var TransactionFactory
     */
    protected $_transaction;

    /**
     * @var CustomercreditFactory
     */
    protected $_customercredit;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @param Session $checkoutSession
     * @param TransactionFactory $transaction
     * @param CustomercreditFactory $customercredit
     */
    public function __construct(
        Session $checkoutSession,
        TransactionFactory $transaction,
        CustomercreditFactory $customercredit
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_transaction = $transaction;
        $this->_customercredit = $customercredit;
    }

    /**
     * Pre-dispatch admin action controller
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        if ($quote->getCustomercreditDiscount()) {
            $order->setCustomercreditDiscount($quote->getCustomercreditDiscount());
            $order->setBaseCustomercreditDiscount($quote->getBaseCustomercreditDiscount());
            $order->setBaseCustomercreditDiscountForShipping($quote->getBaseCustomercreditDiscountForShipping());
            $order->setCustomercreditDiscountForShipping($quote->getCustomercreditDiscountForShipping());
            $order->setMagestoreBaseDiscount($quote->getMagestoreBaseDiscount());
            $order->setMagestoreDiscount($quote->getMagestoreDiscount());
            $order->setMagestoreBaseDiscountForShipping($quote->getMagestoreBaseDiscountForShipping());
            $order->setMagestoreDiscountForShipping($quote->getMagestoreDiscountForShipping());
        }
        if ($quote->getUseCustomerCredit()) {
            $quote->setCustomerCreditAmount(null)
                ->setCreditdiscountAmount(null)
                ->setBaseCreditdiscountAmount(null)
                ->setUseCustomerCredit(false);
        } else {
            $quote->setCustomerCreditAmount(null)
                ->setCreditdiscountAmount(null)
                ->setBaseCreditdiscountAmount(null);
        }
    }
}
