<?php

namespace Snapcommerce\Snapmint\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class RefundObserver implements ObserverInterface
{
    protected LoggerInterface $logger;
    protected OrderRepositoryInterface $orderRepository;
    protected $_helper;

    public function __construct(
        LoggerInterface $logger,
        \Snapcommerce\Snapmint\Helper\Data $helper,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->_helper = $helper;
    }

    /**
     * Execute refund observer
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
            $creditmemo = $observer->getEvent()->getCreditmemo();
            $order = $creditmemo->getOrder();

            $bearer_token = $this->_helper->getConfig(
                "payment/snapmint/merchanttoken"
            );
            $snapmint_base_url = $this->_helper->getSnapmintBaseUrl();

            // Only trigger for your custom payment method
            if ($order->getPayment()->getMethod() !== "snapmint") {
                return;
            }

            $refundAmount = $creditmemo->getGrandTotal();
            $orderIncrementId = $order->getIncrementId();
            $transactionId = $order->getPayment()->getLastTransId();

            $this->logger->info("refundAmount:=" . $refundAmount);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
            $resource = $objectManager->get(
                "Magento\Framework\App\ResourceConnection"
            );
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName("snapmint_order");
            $sql =
                "Select * FROM " .
                $tableName .
                " Where `increment_order_id`=" .
                $orderIncrementId;
            $result = $connection->fetchAll($sql);

            $payload = [
                "order_id" => $orderIncrementId,
                "transaction_id" => $transactionId,
                "refund_amount" => $refundAmount,
                "currency" => $order->getOrderCurrencyCode(),
                "creditmemo_id" => $creditmemo->getIncrementId(),
                "refund_items" => [],
            ];

            // Include refunded items (optional)
            foreach ($creditmemo->getAllItems() as $item) {
                $payload["refund_items"][] = [
                    "sku" => $item->getSku(),
                    "name" => $item->getName(),
                    "qty" => $item->getQty(),
                    "row_total" => $item->getRowTotal(),
                ];
            }

            $this->logger->info(
                "[RefundObserver] Payload: " . json_encode($payload)
            );
            $weburl = $snapmint_base_url;
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $weburl . "v1/merchants/orders/refund_order",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS =>
                    '{
  "order_id": "' .
                    $orderIncrementId .
                    '",
  "refund_amount": ' .
                    $refundAmount .
                    '
 
}',
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $bearer_token,
                ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;

            $this->logger->info($response);
            $responseJson = json_decode($response);

            // Optionally, store refund reference
            $payment = $order->getPayment();
            $payment
                ->setTransactionId("refund-" . $transactionId)
                ->setIsTransactionClosed(true)
                ->setShouldCloseParentTransaction(true);
            $order->addStatusHistoryComment($response);
            $order->save();
        } catch (\Exception $e) {
            $this->logger->error("[RefundObserver] Error: " . $e->getMessage());
            throw new LocalizedException(
                __("Refund API Error: %1", $e->getMessage())
            );
        }
    }
}
