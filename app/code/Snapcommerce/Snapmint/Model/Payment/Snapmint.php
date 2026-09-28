<?php
/**
 * Copyright © Snapcommerce All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Snapcommerce\Snapmint\Model\Payment;
 
class Snapmint extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "snapmint";

    const CODE = 'snapmint';

    const ORDER_STATUS_AUTHORIZED_CODE = 'authorized';
     
    const ORDER_STATUS_AUTHORIZED_LABEL = 'Authorized';
 
    const ORDER_STATUS_CAPUTURED_CODE = 'captured';
     
    const ORDER_STATUS_CAPUTURED_LABEL = 'Captured';
 
    const ORDER_STATUS_AUTHORIZED_PENDING_CODE = 'pending_authorization';
    
    const ORDER_STATUS_AUTHORIZED_PENDING_LABEL = 'Pending Authorization';


    protected $_isOffline = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true; 

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;
    

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
 
    protected $_canCapturePartial = true; 

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;
    
     
    /**
     * Instantiate state and set it to state object.
     *
     * @param string                        $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);      
        
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    /**
     * Check whether method is available
     *
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null $quote
     * @return bool
     */
     
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    } 

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
    
} 