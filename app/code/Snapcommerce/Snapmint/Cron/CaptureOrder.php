<?php
/**
 * Copyright © Snapcommerce All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Snapcommerce\Snapmint\Cron;
use Snapcommerce\Snapmint\Logger\Logger;
class CaptureOrder
{

    protected Logger $logger;
    protected $orderRepository;
    protected $searchCriteriaBuilder;

    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $_curl;

    /** @var \Magento\Sales\Model\OrderFactory $_order **/

    protected $_order;


    /**
    * @param \Snapcommerce\Snapmint\Helper\Data $helper
    */
    public function __construct(
        Logger $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Snapcommerce\Snapmint\Helper\Data $helper,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Sales\Model\OrderFactory $order,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_curl = $curl;
        $this->_order = $order;
    }

    public function execute()
    {

        $merchant_token = $this->_helper->getConfig('payment/snapmint/merchanttoken');
        $merchant_id = $this->_helper->getConfig('payment/snapmint/submerchantid');


        $snapmint_base_url =  $this->_helper->getSnapmintBaseUrl();


        $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;

        $orderStatus = \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_CAPUTURED_CODE;


        //$orderAuthorizeStatus = \Snapcommerce\Snapmint\Model\Payment\Snapmint::ORDER_STATUS_AUTHORIZED_CODE;

        $orderAuthorizeStatus = "payment_pending";

        $date = (new \DateTime())->modify('-3 day');

        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter(
                'status',
                $orderAuthorizeStatus,
                'eq'
            )->addFilter(
                'created_at',
                $date->format('Y-m-d'),
                'gt'
            )->create();

        $orders = $this->_orderRepository->getList($searchCriteria);

        $this->logger->info('---Capture Order Data---'.$orders->getSelect());


        foreach ($orders as $order) {


            $curl_action_url = $snapmint_base_url.'merchant/orders/checkout_order_status?token='.$merchant_token.'&order_ids='.$order->getIncrementId();

            $this->logger->info("url  ".$curl_action_url);

            $this->_curl->get($curl_action_url);


            $this->_curl->addHeader("Content-Type", "application/json");

            $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);


            //response will contain the output of curl request
            //$response = json_decode($this->_curl->getBody(), true);

             //$logger->info("response ".print_r($response, true));


             $array =  json_decode($this->_curl->getBody(), true);

            $this->logger->info("response ".print_r($array, true));
            if($array['status'] == 'Success'){
            for ($i = 0; $i < count($array['orders']); $i++)
            {
                if($array['orders'][$i]['down_payment_status']==1)
                {
                    $order_object = $this->_order->create()->loadByIncrementId($array['orders'][$i]['order_id']);
                    $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;

                    $order_object->setState($orderState)->setStatus($orderStatus);
                    $order_object->setTotalPaid($order_object->getGrandTotal());
                            $order_object->setBaseTotalPaid($order_object->getGrandTotal());
                    $order_object->addStatusHistoryComment(
                        __('Order Captured Successfully')
                    )->setIsCustomerNotified(false);

                    $order_object->save();

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $_quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

                    //added by sandeep
                    $quote = $_quoteFactory->create()->loadByIdWithoutStore($order_object->getQuoteId());
                    if ($quote->getId()) {
                        $this->logger->info('cron set inactive cart capture order ' . $quote->getId());

                        $quote->setIsActive(0)->save();
                    }

                    $this->logger->info("Order Status Changed ".$array['orders'][$i]['order_id']);
                }
            }
            }

            // if($response && isset($response['status']) && $response['status'] == 'success' && $response['order_status'] == 'Captured'){

            //     $order_object = $this->_order->create()->loadByIncrementId($response['order_id']);

            //     if($order_object->getId()){


            //         $order_object->setState($orderState)->setStatus($orderStatus);

            //         $order_object->addStatusHistoryComment(
            //             __('Order Captured Successfully')
            //         )->setIsCustomerNotified(false);

            //         $order->addStatusHistoryComment(
            //             __(json_encode($response))
            //         )->setIsCustomerNotified(false);

            //         $order_object->save();
            //     }

            // }
        }


        return $this;

    }
}

