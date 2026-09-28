<?php
/**
 *
 * @copyright  Snapmint Payment
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Snapcommerce\Snapmint\Helper;

use Snapcommerce\Snapmint\Logger\LoggerPost;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**

     * @var \Magento\Framework\Url
     */

    private $frontUrlModel;
         private $_invoiceService;
    private $_transactionBuilder;
    private $_transaction;
    private $_invoiceSender;
    private $_quoteRepository;
    private $_quoteManagement;
    private $_store;
    private $_scopeConfig;
    protected $_urlBuilder;
    private $_storeManager;
    private $_stockManagement;
    private $_stockIndexerProcessor;
    private $_quoteAPIRepository;
    private $_orderFactory ;
    private $_cartManagement;
    private $_checkoutSession;
    private $_objectManager;
      private $_coreRegistry;
       private $encryptor;
          private $decoder;
                  private $resultFactory;
             protected $urlEncoder;
    protected LoggerPost $logger;


    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Locale\Resolver $store,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Url $frontUrlModel,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Url\DecoderInterface $decoder,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        LoggerPost $logger
    )
    {
        $this->_invoiceService = $invoiceService;
        $this->_transactionBuilder = $transactionBuilder;

        $this->_transaction = $transaction;
        $this->_invoiceSender = $invoiceSender;

        $this->_store = $store;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlBuilder = $urlBuilder;
        $this->_storeManager = $storeManager;
        $this->frontUrlModel = $frontUrlModel;
        $this->_coreRegistry = $coreRegistry;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->encryptor     = $encryptor;
        $this->decoder = $decoder;
        $this->resultFactory = $resultFactory;
        $this->urlEncoder = $urlEncoder;
        $this->logger = $logger;

    }


    public function getConfig($config_path) {
        return $this->_scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    public function getSnapmintFailureReturnUrl($store_id = null){
        return $this->_storeManager->getStore(
            $store_id
        )->getUrl(
            'checkout/onepage/failure',
            [
                '_secure' => true
            ]
        );
    }



    public function getSnapmintReturnUrl($store_id = null){
        return $this->_storeManager->getStore(
            $store_id
        )->getUrl(
            'snapmint/ipn/callbacksnapmint',
            [
                '_secure' => true
            ]
        );
    }

    public function getSnapmintBaseUrl(){

        $environment =  $this->getConfig('payment/snapmint/environment');

        $url = 'https://api.snapmint.com/';

         if($environment == 'sandbox')
            $url = 'https://sandboxapi.snapmint.com/';


        return $url;
    }

    public function getSnapmintJSBaseUrl(){

        $environment =  $this->getConfig('payment/snapmint/environment');

        $url = 'https://assets.snapmint.com/';

        if($environment == 'sandbox')
            $url = 'https://qa2.snapmint.com/';


        return $url;
    }

    public function _registerPaymentCapture($order, $transactionId, $amount, $message, $postdata) {

        //$logger->info('in transaction for '.$order->getId());

        if ($order->getId() && is_numeric($order->getId())) {
            $order = $this->_orderFactory->create()->load($order->getId());
            if ($order && $order->getId()) {
                $this->logger->info('in transaction for '.$order->getId());
                $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
                $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->_checkoutSession->setLastOrderId($order->getId());
                $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->_checkoutSession->setLastOrderStatus($order->getStatus());
            }
        }
        try {
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

            if ($order->getId() && is_numeric($order->getId())) {
                /** @var EncryptorInterface $encryptor */
                $this->logger->info('in transaction for q'.$order->getId());
                $encryptedOrderId = $this->encryptor->encrypt($order->getId());
            // Redirect to success page with encrypted order id

                $urlEncodedOrderId = $this->urlEncoder->encode($encryptedOrderId);
                $this->logger->info('in transaction for '.$order->getId());
                $resultRedirect->setPath('checkout/onepage/success',['order_id' => $urlEncodedOrderId]);
                $this->logger->info('in transaction for '.$order->getIncrementId());

                $payment = $order->getPayment();

                $payment->setLastTransId($transactionId);
                $payment->setTransactionId($transactionId);
                $payment->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$postdata]
                );

                $formatedPrice = $order->getBaseCurrency()->formatTxt(
                    $order->getGrandTotal()
                );

                $message = __('The  amount is %1.', $formatedPrice);


        $tranTypeValues = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;

        $transaction = $this->_transactionBuilder->setPayment($payment)
        ->setOrder($order)
        ->setTransactionId($transactionId)
        ->setAdditionalInformation(
            [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$postdata]
        )
        ->setFailSafe(true)
        ->build($tranTypeValues);

        $order->save();

        $order->addStatusHistoryComment(
            __('snapmint transaction id #%1.',$transactionId )
        );


                $this->logger->info('in transaction for '.$order->getIncrementId());

        $createinvoice = $this->getConfig('payment/snapmint/createinvoice');

        if ($order->canInvoice() && $createinvoice == 1) {

            $this->logger->info('invoice create for '.$order->getIncrementId());
        $invoice = $this->_invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->save();
        $transactionSave = $this->_transaction->addObject(
            $invoice
        )->addObject(
            $invoice->getOrder()
        );
        $transactionSave->save();
        $this->_invoiceSender->send($invoice);
            //Send Invoice mail to customer
        $order->addStatusHistoryComment(
            __('Notified customer about invoice creation #%1.', $invoice->getId())
        )
        ->setIsCustomerNotified(true)
        ->save();

        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$order->getEmailSent()) {
            $orderSender->send($order);
            $order->addStatusHistoryComment(
                __('You notified customer about invoice #%1.', $invoice->getIncrementId())
            )->setIsCustomerNotified(
                true
            )->save();
       }
   }

    } else {
        $resultRedirect->setPath('checkout/onepage/success');
    }



} catch (\Exception $e) {

            $orderSender->info('ERROR: '.$e->getMessage());

    return $e->getMessage();
}





}

  public function moveOrderToProcessing($order, $post)
    {

        $status = $post['status'];
          $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
          $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;

        if ($order->isCanceled()) {
            $productStockQty = [];
            foreach ($order->getAllVisibleItems() as $item) {
                  $productStockQty[$item->getProductId()] = $item->getQtyCanceled();
                foreach ($item->getChildrenItems() as $child) {
                    $productStockQty[$child->getProductId()] = $item->getQtyCanceled();
                    $child->setQtyCanceled(0);
                    $child->setTaxCanceled(0);
                    $child->setDiscountTaxCompensationCanceled(0);
                }
                  $item->setQtyCanceled(0);
                  $item->setTaxCanceled(0);
                  $item->setDiscountTaxCompensationCanceled(0);
            }

            $order->setSubtotalCanceled(0);
            $order->setBaseSubtotalCanceled(0);
            $order->setTaxCanceled(0);
            $order->setBaseTaxCanceled(0);
            $order->setShippingCanceled(0);
            $order->setBaseShippingCanceled(0);
            $order->setDiscountCanceled(0);
            $order->setBaseDiscountCanceled(0);
            $order->setTotalCanceled(0);
            $order->setBaseTotalCanceled(0);

            $order->setState($orderState)
            ->setStatus($order->getConfig()->getStateDefaultStatus($orderState));
            if (!empty($comment)) {
                  $order->addStatusHistoryComment($comment, false);
            }

          /* Reverting inventory */
            $itemsForReindex = $this->_stockManagement->registerProductsSale(
                $productStockQty,
                $order->getStore()->getWebsiteId()
            );

            $productIds = [];
            foreach ($itemsForReindex as $item) {
                      $item->save();
                      $productIds[] = $item->getProductId();
            }

            if (!empty($productIds)) {
                  $this->_stockIndexerProcessor->reindexList($productIds);
            }
            $order->setInventoryProcessed(true);
        }

        $order_value    = number_format((float)$order->getGrandTotal(), 2, '.', '');

        $transactionId = $post['snapmint_id'];
           $message = 'Order ID: ' . $post['order_id'] . "\n";

             $this->_registerPaymentCapture($order,$post['snapmint_id'],$post['order_value'],$message,$post);

        $order->setTotalPaid($order->getGrandTotal());
        $order->setBaseTotalPaid($order->getGrandTotal());

        $order->setState($orderState)->setStatus($orderStatus);

        $order->addStatusHistoryComment('Order Processed from Webhook');

        $order->save();

        $message = "Order Processed from Webhook";
       // $this->_logger->info("Snapmint Webhook Ends.");
        return $message;
    }

}
