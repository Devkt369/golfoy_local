<?php

namespace ArtM\EstimateDelivery\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\GridPool;
use Psr\Log\LoggerInterface;

class SaveEstimationDates implements ObserverInterface
{
    protected $resourceConnection;
    protected $gridPool;
    protected $logger;
    protected $batchDeliveryService;

    public function __construct(
        ResourceConnection $resourceConnection,
        GridPool $gridPool,
        LoggerInterface $logger,
        \ArtM\EstimateDelivery\Model\BatchDeliveryService $batchDeliveryService
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->gridPool = $gridPool;
        $this->logger = $logger;
        $this->batchDeliveryService = $batchDeliveryService;
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();

            if (!$order || !$order->getId()) {
                $this->logger->warning('SaveEstimationDates: No order found in event.');
                return;
            }

            $productIds = [];
            foreach ($order->getAllVisibleItems() as $item) {
                // For configurable items, use selected simple product id (child).
                $productId = (int)$item->getProductId();
                $simpleOption = $item->getOptionByCode('simple_product');
                if ($simpleOption && $simpleOption->getProduct()) {
                    $productId = (int)$simpleOption->getProduct()->getId();
                }
                if ($productId > 0) {
                    $productIds[] = $productId;
                }
            }

            $productIds = array_values(array_unique($productIds));

            if (empty($productIds)) {
                $this->logger->warning('SaveEstimationDates: No product IDs for order ' . $order->getIncrementId());
                return;
            }

            $this->logger->info('SaveEstimationDates: Calculating via BatchDeliveryService for order ' . $order->getIncrementId() . ', products: ' . implode(',', $productIds));

            $results = $this->batchDeliveryService->getBatchDelivery($productIds);
            $deliveryDates = isset($results['delivery_dates']) && is_array($results['delivery_dates'])
                ? $results['delivery_dates']
                : [];

            if (empty($deliveryDates)) {
                $this->logger->warning('SaveEstimationDates: No dates returned for order ' . $order->getIncrementId());
                return;
            }

            sort($deliveryDates);
            $dates = [
                'estimated_delivery_early' => $deliveryDates[0],
                'estimated_delivery_last' => $deliveryDates[count($deliveryDates) - 1]
            ];

            $this->logger->info('SaveEstimationDates: Dates calculated - early: ' . $dates['estimated_delivery_early'] . ', last: ' . $dates['estimated_delivery_last']);

            $connection = $this->resourceConnection->getConnection();

            // Update sales_order directly
            $connection->update(
                $this->resourceConnection->getTableName('sales_order'),
                [
                    'estimated_delivery_early' => $dates['estimated_delivery_early'],
                    'estimated_delivery_last'  => $dates['estimated_delivery_last']
                ],
                ['entity_id = ?' => $order->getId()]
            );

            // Update sales_order_grid directly
            $affected = $connection->update(
                $this->resourceConnection->getTableName('sales_order_grid'),
                [
                    'estimated_delivery_early' => $dates['estimated_delivery_early'],
                    'estimated_delivery_last'  => $dates['estimated_delivery_last']
                ],
                ['entity_id = ?' => $order->getId()]
            );

            // If grid row does not exist yet, refresh it and update again.
            if ((int)$affected === 0) {
                $this->gridPool->refreshByOrderId((int)$order->getId());
                $connection->update(
                    $this->resourceConnection->getTableName('sales_order_grid'),
                    [
                        'estimated_delivery_early' => $dates['estimated_delivery_early'],
                        'estimated_delivery_last'  => $dates['estimated_delivery_last']
                    ],
                    ['entity_id = ?' => $order->getId()]
                );
            }

            $this->logger->info('SaveEstimationDates: Saved to DB for order ' . $order->getIncrementId());

        } catch (\Exception $e) {
            $this->logger->error('SaveEstimationDates error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
        }
    }
}