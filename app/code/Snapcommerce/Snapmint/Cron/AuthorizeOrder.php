<?php
/**
 * Copyright © Snapcommerce All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Snapcommerce\Snapmint\Cron;
use \Psr\Log\LoggerInterface;
use Snapcommerce\Snapmint\Logger\Logger;

class AuthorizeOrder
{
    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $_curl;

    /** @var \Magento\Sales\Model\OrderFactory $_order **/

    protected $_order;

    protected Logger $logger;

    /**
    * @param \Snapcommerce\Snapmint\Helper\Data $helper
    */
    public function __construct(
        Logger $logger,
        \Magento\Sales\Model\OrderFactory $order,
        \Snapcommerce\Snapmint\Helper\Data $helper,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_order = $order;
        $this->_curl = $curl;
    }

    public function execute()
    {
        try{

        $merchant_token = $this->_helper->getConfig('payment/snapmint/merchanttoken');


        $snapmint_base_url =  $this->_helper->getSnapmintBaseUrl();

        $curl_action_url = $snapmint_base_url.'merchant/orders/get_authorized_orders?token='.$merchant_token.'&per_page=20';



        //get method
        $this->_curl->get($curl_action_url);

        $this->_curl->addHeader("Content-Type", "application/json");

        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);


        //response will contain the output of curl request
        $response = json_decode($this->_curl->getBody(), true);

        $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $orderStatus = \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_AUTHORIZED_CODE;


        if($response && isset($response['status']) && $response['status'] == 'success'){
            foreach ($response['orders'] as $orders) {

                $order = $this->_order->create()->loadByIncrementId($orders['order_id']);

                if($order && ($order->getStatus() == \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_AUTHORIZED_PENDING_CODE || $order->getStatus() == 'pending')) {


                    $order->addStatusHistoryComment(
                        __('Order Authorized Successfully')
                    )->setIsCustomerNotified(false);

                    $order->addStatusHistoryComment(
                        __(json_encode($orders))
                    )->setIsCustomerNotified(false);

                    if($order->getStatus() == 'pending'){

                        $order_value    = number_format((float)$order->getGrandTotal(), 2, '.', '');
                        $message        = 'Order Authorized from Cron';
                        $this->_helper->_registerPaymentCapture($order, $orders['snapmint_id'], $order_value, $message, $orders);

                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $_quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

                        //added by sandeep
                        $quote = $_quoteFactory->create()->loadByIdWithoutStore($order->getEntityId());
                        if ($quote->getId()) {
                            $this->_logger->info('order id ' . $quote->getId());
                            $this->_logger->info('cron set inactive cart Authorized order ' . $quote->getId());

                            $quote->setIsActive(0)->save();
                        }
                    }



                    $order->setState($orderState)->setStatus($orderStatus);


                    $order->save();
                }
            }

        }} catch (\Exception $e) {
            $this->_logger->info("Snapmint: error processing callback ".$e->getMessage());;
        }

        return $this;

    }
}
