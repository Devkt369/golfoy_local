<?php
namespace Snapcommerce\Snapmint\Controller\Refund;

class SnapmintRefund extends \Magento\Framework\App\Action\Action
{
     /**
      * @var \Magento\Checkout\Model\Session
      */
    protected $_checkoutSession;

    /**
     * @var \Snapcommerce\Snapmint\Model\Payment\Snapmint
     */
    protected $_paymentMethod;

     /**
      * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
      */
    protected $_resultJsonFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

     /**
      * @var \Snapcommerce\Snapmint\Helper\Data
      */
    protected $_helper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    protected $orderRepository;

    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $_curl;
    protected $_resource;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $_objectManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_quoteAPIRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Snapcommerce\Snapmint\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteAPIRepository
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Snapcommerce\Snapmint\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Api\CartRepositoryInterface $quoteAPIRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\HTTP\Client\Curl $curl,
         \Magento\Framework\App\ResourceConnection $resource
    ) {

        $this->_customerSession         = $customerSession;
        $this->_checkoutSession         = $checkoutSession;
        $this->_resultJsonFactory       = $resultJsonFactory;
        $this->_orderFactory = $orderFactory;
        $this->_logger                  = $logger;
        $this->_helper                  = $helper;
        $this->_objectManager           = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_quoteAPIRepository      = $quoteAPIRepository;
        $this->orderRepository = $orderRepository;
        $this->_curl = $curl;
         $this->_resource = $resource;
        parent::__construct($context);
    }

    
    public function execute()
    {
        
 $orderId = $this->getRequest()->getParam('order_id');
        $this->_logger->info('---Redirect Data---');
         $this->_logger->info( $orderId);


          try {
            $order = $this->orderRepository->get($orderId);
            $orderAmount = $order->getGrandTotal();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();
$tableName = $resource->getTableName('snapmint_order');
$sql = "Select * FROM " . $tableName ." Where `order_id`=".$orderId;
$result = $connection->fetchAll($sql);
     $this->_logger->info('---Redirect DataDb---');
         $this->_logger->info($result[0]['snapmint_order_id']);

            $bearer_token = $this->_helper->getConfig('payment/snapmint/merchanttoken');
       


            $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.snapmint.com/v1/merchants/orders/refund_order',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{"order_id":"'.$result[0]['snapmint_order_id'].'","refund_amount":"'.$result[0]['snapmint_order_amount'].'"}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: Bearer '.$bearer_token
  ),
));

$response = curl_exec($curl);

curl_close($curl);
// echo $response;

      $this->_logger->info( $response);
 $responseJson = json_decode($response);

            if (json_last_error() === JSON_ERROR_NONE) {
                if(is_object($responseJson)){
                    if($responseJson->status=='Success'){
                        $result = ['success' => true, 'message' => $responseJson->message];
                    }else {
                        $result = ['success' => false, 'message' => $responseJson->message];
                    }
                }
            }
            else{
                $response = strip_tags($response);
                $result = ['success' => false, 'message' => $response];
            }

            // // if (json_last_error() === JSON_ERROR_NONE) {
            // //     if(is_object($response)){
            // //         if($responseJson->status=='Success'){
            // //             $result = ['success' => true, 'message' => $response];
            // //         }else {
            // //             $result = ['success' => false, 'message' => $response];
            // //         }
            // //     }
            // }
            // else{
            //     $response = strip_tags($response);
            //     $result = ['success' => false, 'message' => $response];
            // }
            // Close the cURL session
            // curl_close($curl);

            // Process the API response (if needed)
            // You can parse and handle the response as per your API's format
            

        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );

      } 
}
