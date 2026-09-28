<?php

namespace ArtM\ShipmentTracking\Cron;

use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;
use ArtM\ShipmentTracking\Logger\Logger;
use ArtM\ShipmentTracking\Model\Order\UpdateOrderStatus as OrderStatusUpdater;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use ArtM\ShipmentTracking\Model\Service\TrackingStatusLogger;
use ArtM\ShipmentTracking\Helper\Data;

class CronUpdateOrderStatus
{
    protected $trackCollectionFactory;
    protected $orderStatusUpdater;
    protected $logger;
    protected $orderCollectionFactory;
    protected $trackingStatusLogger;
    protected $helper;

    public function __construct(
        TrackCollectionFactory $trackCollectionFactory,
        Logger $logger,
        OrderStatusUpdater $orderStatusUpdater,
        OrderCollectionFactory $orderCollectionFactory,
        TrackingStatusLogger $trackingStatusLogger,
        Data $helper
    ) {
        $this->trackCollectionFactory = $trackCollectionFactory;
        $this->logger = $logger;
        $this->orderStatusUpdater = $orderStatusUpdater;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->trackingStatusLogger = $trackingStatusLogger;
        $this->helper = $helper;
    }

    public function execute()
    {
        if (!$this->helper->isOrderStatusCronEnabled()) {
            $this->logger->info(
                '[Shipment Tracking] Order Status Cron disabled from admin configuration.'
            );
            return;
        }
        $this->logger->info("=== Cron UpdateOrderStatus Started ===");

        $fromDate = (new \DateTime())->modify('-2 months')->format('Y-m-d H:i:s');
        // $lastCheckedThreshold = (new \DateTime())->modify('-40 minutes')->format('Y-m-d H:i:s');

        $trackCollection = $this->trackCollectionFactory->create();

        $trackCollection->getSelect()
            ->joinInner(
                ['order' => $trackCollection->getTable('sales_order')],
                'main_table.order_id = order.entity_id',
                ['order_status' => 'status', 'order_increment_id' => 'increment_id']
            )
            // ->where('main_table.order_id = ?', '154011')
            ->where('order.created_at >= ?', $fromDate)
            ->where('order.pos_id IS NULL')
            ->where('order.status NOT IN (?)', ['delivered', 'canceled', 'closed'])
            ->where('main_table.track_number IS NOT NULL')
            ->where('main_table.track_number != ""')
            ->where('main_table.delivered_at IS NULL')
            // ->where(
            //     'main_table.last_checked_at IS NULL OR main_table.last_checked_at < ?',
            //     $lastCheckedThreshold
            // )
            ->order('main_table.last_checked_at ASC NULLS FIRST')
            ->order('main_table.entity_id DESC')
            ->limit(500);
           
        $ordersToCheck = [];
        foreach ($trackCollection as $track) {
            $orderId = (int)$track->getOrderId();
            $ordersToCheck[$orderId][] = $track;
        }

        $processed = 0;

        // print_r($trackCollection->getSize());die;
        foreach ($ordersToCheck as $orderId => $tracks) {
            $processed++;
            $incrementId = $tracks[0]->getData('order_increment_id');
            $this->logger->info("Processing Order ID: {$orderId} | Tracks: " . count($tracks));
            echo "Order ID {$orderId} Processing\n";
            // Always update tracking status
            $allDelivered = true;

            foreach ($tracks as $track) {
                sleep(1); // To avoid hitting API rate limits
                $title = strtolower(trim($track->getTitle() ?? ''));
                $trackNumber = $track->getTrackNumber();

                if (empty($trackNumber)) {
                    $allDelivered = false;
                    continue;
                }

                // if (stripos($title, 'shree maruti') !== false ) {
                //     continue;
                // }
                
                $isDelivered = $this->orderStatusUpdater->checkOrderShipmentStatus(
                    $title,
                    $trackNumber,
                    $orderId,
                    $track
                );

                if (!$isDelivered) {
                    $allDelivered = false;
                }
            }

            // Only mark as Delivered if conditions met
            if ($allDelivered && $this->isOrderFullyShipped($orderId)) {
                $this->orderStatusUpdater->updateToDelivered($orderId);
                echo "Order ID {$orderId} marked as DELIVERED\n";
                $this->trackingStatusLogger->logDeliveredOrder(
                    (int)$orderId,
                    (string)$incrementId
                );

                $this->logger->info("✅ Order ID {$orderId} marked as DELIVERED");
            } else {
                $this->logger->info("Order ID {$orderId} - Not ready for Delivered yet (tracking or shipment incomplete).");
            }
        }

        $this->logger->info("=== Cron Finished. Processed {$processed} orders ===");
    }

    /**
     * Reliable check: Is the entire order fully shipped?
     */
    /**
     * Reliable check: Is the entire order fully shipped?
     * Uses shipment table as primary source for better accuracy
     */
    public function isOrderFullyShipped($orderId)
    {
        try {
            $orderCollection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('entity_id', $orderId)
                ->addFieldToSelect(['total_qty_ordered', 'status'])
                ->getFirstItem();

            // echo "Checking Order ID {$orderId}...\n";

            if (!$orderCollection->getId()) {
                // echo "Order not found!\n";
                return false;
            }

            // echo "Order Status: " . $orderCollection->getStatus() . "\n";
            $totalOrdered = (float)$orderCollection->getTotalQtyOrdered();
            // echo "Total Ordered: {$totalOrdered}\n";

            if ($totalOrdered <= 0) {
                return false;
            }

            $connection = $orderCollection->getResource()->getConnection();

            // More reliable query: Join with shipment table
            $select = $connection->select()
                ->from(['si' => $connection->getTableName('sales_shipment_item')], ['total_shipped' => 'COALESCE(SUM(si.qty), 0)'])
                ->joinInner(
                    ['s' => $connection->getTableName('sales_shipment')],
                    's.entity_id = si.parent_id',
                    []
                )
                ->where('s.order_id = ?', $orderId);

            $totalShipped = (float)$connection->fetchOne($select);
            
            $isFullyShipped = ($totalShipped >= $totalOrdered);
            
            return $isFullyShipped;
        } catch (\Exception $e) {
            $this->logger->error("Error in isOrderFullyShipped for order {$orderId}: " . $e->getMessage());
            echo "Exception: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
