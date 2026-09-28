<?php
/**
 * @copyright  Snapmint
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Snapcommerce\Snapmint\Controller\Ipn;

use Magento\Framework\App\Config\ScopeConfigInterface;

use Magento\Framework\App\Action\Action as AppAction;
use Snapcommerce\Snapmint\Logger\LoggerPost;

class CallbackSnapmint extends AppAction
{
    /**
    * @var \Snapmint\Icp\Model\PaymentMethod
    */
    protected $_paymentMethod;

    /**
    * @var \Magento\Sales\Model\Order
    */
    protected $_order;

    /**
    * @var \Magento\Sales\Model\OrderFactory
    */
    protected $_orderFactory;

    /**
    * @var Magento\Sales\Model\Order\Email\Sender\OrderSender
    */
    protected $_orderSender;

    /**
    * @var LoggerPost
    */
    protected LoggerPost $_logger;

     /**
     * @var \Snapcommerce\Snapmint\Helper\Data
     */
     protected $_helper;

     /**
     * @var \Magento\Framework\App\Request\Http
     */

      protected $request;


     protected $invoiceService;

     protected $transaction;

     protected $invoiceSender;
      protected $_client;



    /**
    * @param \Magento\Framework\App\Action\Context $context
    * @param \Magento\Framework\App\Request\Http $request
    * @param \Magento\Sales\Model\OrderFactory $orderFactory
    * @param \Snapcommerce\Snapmint\Model\Payment\Snapmint $paymentMethod
    * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    * @param LoggerPost $logger
    * @param \Snapcommerce\Snapmint\Helper\Data $helper
    */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Snapcommerce\Snapmint\Model\Payment\Snapmint $paymentMethod,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        LoggerPost $logger,
        \Snapcommerce\Snapmint\Helper\Data $helper
    ) {
        $this->_paymentMethod = $paymentMethod;
        $this->_orderFactory = $orderFactory;
        $this->_client = $this->_paymentMethod->getClient();
        $this->_orderSender = $orderSender;
        $this->_logger = $logger;
        $this->request = $request;
        $this->_helper = $helper;

        parent::__construct($context);
    }

    /**
    * Handle POST request to Snapmint callback endpoint.
    */
    public function execute()
    {

       try {
            // Cryptographically verify authenticity of callback
        if($this->request->getPost())
       {
            $postdata   = $this->getRequest()->getPost();
            $order_id   = $postdata['order_id'];
            if(!$order_id){


            $this->_logger->info('back request');
            $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $this->_logger->info($actual_link);
            $incrId = substr(strrchr($actual_link, "/"), 1);
            $this->_logger->info($incrId);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $collection = $objectManager->create('Magento\Sales\Model\Order');
            $order = $collection->loadByIncrementId($incrId);
            $_checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
            $_quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

            $quote = $_quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
            $this->_logger->info($quote->getId());
            if ($quote->getId()) {

                   $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cart.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info('postdata ' . $quote->getId());


                $quote->setIsActive(1)->setReservedOrderId(null)->save();
                $_checkoutSession->replaceQuote($quote);

                $this->_redirect('checkout/cart');
            }
            $order->cancel()->save();
              }
            $this->paymentAction();
        }
        else

        {
            $this->_logger->error("Snapmint: no post back data received in callback");

            return $this->_failure();
        }

    } catch (\Exception $e) {
        $this->_logger->error("Snapmint: error processing callback");
        $this->_logger->error($e->getMessage());
        return $this->_failure();
    }

    //  $this->_logger->addInfo("Snapmint Transaction END from Snapmint");
}

protected function paymentAction()
{
    $merchant_key = $this->_helper->getConfig('payment/snapmint/merchantkey');
    $merchant_token = $this->_helper->getConfig('payment/snapmint/merchanttoken');

    try{
        if ($this->getRequest()->isPost()) {

            $postdata   = $this->getRequest()->getPost();


            $this->_logger->info('---Post Data---');


            $order_id   = $postdata['order_id'];
            $this->_loadOrder($order_id);

            $order_value = number_format((float)$this->_order->getGrandTotal(), 2, '.', '');
            $postdata_hash   = $postdata['checksum_hash'];
            $status = $postdata['status'];
            $order_id=$postdata['order_id'];
            $loan_amount=$postdata['emi_amount'];
            $snap_id=$postdata['snapmint_id'];
            $down_payment_status=$postdata['down_payment_status'];
            $dp_amount=$postdata['down_payment_amt'];


            $billingAddress = $this->_order->getBillingAddress();

            $firstname = $billingAddress->getData('firstname');
            $lastname = $billingAddress->getData('lastname');
            $email = $postdata['email'];

            $fullname = $firstname.' '.$lastname;

            $checkhash = hash('sha512', $merchant_token.'|'.$status.'|'.$order_id.'|'.$order_value.'|'.$fullname.'|'.$email.'|'.$merchant_key);

            $this->_logger->info('---order successsss---');
            $this->_logger->info($merchant_token.'|'.$status.'|'.$order_id.'|'.$order_value.'|'.$fullname.'|'.$email.'|'.$merchant_key);
            $this->_logger->info($checkhash);
            $this->_logger->info('---order posthash---');
            $this->_logger->info($postdata_hash);


            $this->_logger->info('status - '. $status);
            $this->_logger->info('status - '. $order_id);


            $message = 'Order ID: ' . $order_id . "\n";

            $historymessage = $message;

            if($postdata_hash == $checkhash) {



                $this->_logger->info('in checkhash');

                if(isset($status) && $status == 'success'  && isset($dp_amount) && isset($loan_amount) && isset($snap_id) && ($down_payment_status== "success")) {
                        // success

                    $this->_logger->info('in success');

                    $this->_createSnapmintComment('Customer Redirected Successfully from Snapmint PG');

                    $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;


                    $this->_order->setState($orderState)->setStatus($orderStatus);
                    $this->_createSnapmintComment('Order status change to processing');
                    $this->_order->save();

                    // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    // $_quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

                    // //added by sandeep
                    // $quote = $_quoteFactory->create()->loadByIdWithoutStore($this->_order->getQuoteId());
                    // if ($quote->getId()) {
                    //     $quote->setIsActive(0)->save();
                    // }


                    $this->_helper->_registerPaymentCapture($this->_order,$postdata['snapmint_id'],$postdata['order_value'],$message,$postdata);

                        //$logger->info('in success helper');
                    $this->_redirect('checkout/onepage/success');
                } else if(isset($status) && ($status == 'pending' || $status == 'on-hold' || $status == 'failure'))  {
                        // success
                    $this->_logger->info('in success else');

                    $this->_logger->info('status - '. $status);

                    if($status == 'pending'){
                        $message = 'Thank you for shopping with us. Right now your payment staus is pending, We will keep you posted regarding the status of your order through e-mail';
                        $historymessage = 'Cardless EMI (Powered by Snapmint) status is pending';
                    }

                    if($status == 'on-hold'){
                        $message = 'Thank you for shopping with us. Right now your payment staus is pending, We will keep you posted regarding the status of your order through e-mail';
                        $historymessage = 'Cardless EMI (Powered by Snapmint) status is on hold';
                    }

                    if($status == 'failure'){
                        $this->_logger->info('---order fail1---');
                       $message = 'The transaction has been declined.';
                       $historymessage = 'Cardless EMI (Powered by Snapmint) status is failure';

                        $this->_logger->info($this->_order->getQuoteId());
                       $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                       $_checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
                       $_quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

                       $quote = $_quoteFactory->create()->loadByIdWithoutStore($this->_order->getQuoteId());
                        $this->_logger->info($quote->getId());
                       if ($quote->getId()) {

                         $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cart.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info('status failure ' . $quote->getId());

                        $quote->setIsActive(1)->setReservedOrderId(null)->save();
                        $_checkoutSession->replaceQuote($quote);

                        $this->_redirect('checkout/cart');
                    }



                }
                $this->_order->cancel()->save();

                $this->messageManager->addError("<strong>Error:</strong> $message <br/>");

                $this->_createSnapmintComment($historymessage);


                if($status == 'on-hold'){
                    $order_state_hold = \Magento\Sales\Model\Order::STATE_HOLDED;
                    $this->_order->setState($order_state_hold)->setStatus($order_state_hold);
                    $this->_order->save();
                }

              //  $this->_redirect('checkout/onepage/success');
                    $this->_logger->info('Snapmint Response signature did not match 231');
            } else {  $this->_logger->info('---order fail2---');

                    $this->_logger->info('in else');

            $this->_createSnapmintComment($historymessage);
            $this->_order->cancel()->save();

            $this->_logger->error("Snapmint Response Order cancelled ..");

            $this->messageManager->addError("<strong>Error:</strong> $message <br/>");

            $this->_redirect('checkout/onepage/failure');
        }
    } else {
                $this->_logger->info('Snapmint Response signature does not match. You might have received tampered data');

        $this->_createSnapmintComment("Snapmint Response signature does not match. You might have received tampered data", true);

        $this->_order->cancel()->save();

                $this->_logger->info('Snapmint Response signature did not match ');
        $this->_logger->error("Snapmint Response signature did not match ");

        $this->messageManager->addError("<strong>Error:</strong> Snapmint Response signature does not match. You might have received tampered data");
        $this->_redirect('checkout/onepage/failure');

    }
   // $this->_redirect("checkout/onepage/success");
   // $logger->info('Snapmint Response signature did not match 257');

}else{
        //       $this->_logger->addError("Snapmint Response Empty Post ");
}
} catch (\Exception $e) {

    $this->_logger->info('ERROR: '.$e->getMessage());

    return $e->getMessage();
}
}



protected function _loadOrder($order_id)
{
    $this->_order = $this->_orderFactory->create()->loadByIncrementId($order_id);

    if (!$this->_order && $this->_order->getId()) {
        throw new Exception('Could not find Magento order with id $order_id');
    }
}

    //AA Done
protected function _success()
{
    $this->getResponse()
    ->setStatusHeader(200);
}

    //AA Done
protected function _failure()
{
    $this->getResponse()
    ->setStatusHeader(400);
}

    /**
    * Returns the generated comment or order status history object.
    *
    * @return string|\Magento\Sales\Model\Order\Status\History
    */
    //AA Done
    protected function _createSnapmintComment($message = '')
    {
        if ($message != '')
        {
            $message = $this->_order->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }

        return $message;
    }

}
