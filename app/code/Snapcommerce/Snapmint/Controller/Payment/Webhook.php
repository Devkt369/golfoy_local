<?php

namespace Snapcommerce\Snapmint\Controller\Payment;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Controller\ResultFactory;

class Webhook extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Sales\Model\OrderFactory $_order **/
    protected $_order;

    /** @var \Psr\Log\LoggerInterface $_logger **/
    protected $_logger;

    /**
     * @var \Snapcommerce\Snapmint\Helper\Data $_helper
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $_stockManagement
     */
    protected $_stockManagement;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $_stockIndexerProcessor
     */
    protected $_stockIndexerProcessor;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $_quoteRepository;

     /**
      * @param \Magento\Framework\App\Action\Context $context
      * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
      * @param \Psr\Log\LoggerInterface $logger
      * @param \Magento\Sales\Model\OrderFactory $order
      * @param \Snapcommerce\Snapmint\Helper\Data $helper
      * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
      * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
      * @param \Magento\Framework\Event\ManagerInterface $eventManager
      */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\OrderFactory $order,
        \Snapcommerce\Snapmint\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {

        $this->_logger                  = $logger;
        $this->_helper                  = $helper;
        $this->_order                   = $order;
        $this->_scopeConfig             = $scopeConfig;
        $this->_resultJsonFactory       = $resultJsonFactory;
        $this->_eventManager            = $eventManager;
        $this->_quoteRepository         = $quoteRepository;
        return parent::__construct($context);
    }

    /**
     * Create Order
     */

    public function execute()
    {
        $this->_logger->info("In Snapmint Webhook.");

        $result = $this->_resultJsonFactory->create();

        $response = [];
        $response['order_placed'] = 0;

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $isEnabled = $this->_scopeConfig->getValue('payment/snapmint/enable_webhook', $storeScope);

        if ($isEnabled) {

            $this->_logger->info("Snapmint Webhook Started.");
            
            //$content = $this->getRequest()->getRawBody();

            $content = file_get_contents('php://input');

            $post = (array) json_decode($content);

            $this->_logger->info('Webhook post data');
            $this->_logger->info(print_r($post, true));//phpcs:ignore

            $order_id = $post['order_id'];

            if (isset($post['status']) && ($post['status'] == 'Success') || ($post['status'] == 'success')) {

                $orderid                    = $post['order_id'];
                $status                     = $post['status'] ;
                $postdata_hash              = $post['checksum_hash'];
                $email                      = $post['email'];
                $mobile                     = $post['mobile'];
                $order_value                = $post['order_value'];
                $fullname                   = $post['full_name'];
                $SnapmintId                 = $post['snapmint_id'];
                
                $this->_logger->info("Order Webhook Processing for ". $order_id);
                $merchant_token = $this->_helper->getConfig('payment/snapmint/merchanttoken');

                //check if order exists

              //$SnapmintId trim($SnapmintId,"Loan-");
                $order = $this->_order->create()->loadByIncrementId($order_id);
                if ($order->getId()) {
                    $response = $this->_helper->moveOrderToProcessing($order, $post);
                } 
            } else {
                $message = 'Snapmint Order : '.$order_id .' Status : '. $post['status'];
                $response['order_placed'] = 0;
                $response['message'] = $message;
                $this->_logger->info($message);
            }
        } else {
            $response['order_placed'] = 0;
            $message = 'Snapmint Order : Webhook Not Enabled';
            $response['message'] = $message;
            $this->_logger->info($message);
        }

        $this->_logger->info("In Snapmint Webhook End.");

        return $result->setData($response);
    }
}
