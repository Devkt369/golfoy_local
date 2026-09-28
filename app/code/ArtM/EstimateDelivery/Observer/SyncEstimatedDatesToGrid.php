<?php

namespace ArtM\EstimateDelivery\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\GridPool;
use Psr\Log\LoggerInterface;

class SyncEstimatedDatesToGrid implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GridPool
     */
    protected $gridPool;

    /**
     * Constructor
     *
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GridPool $gridPool,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->gridPool = $gridPool;
        $this->logger = $logger;
    }

    /**
     * Sync estimated dates from sales_order to sales_order_grid
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();

            if (!$order || !$order->getId()) {
                return;
            }

            $orderId = (int)$order->getId();
            $connection = $this->resourceConnection->getConnection();
            $orderTable = $this->resourceConnection->getTableName('sales_order');
            $gridTable = $this->resourceConnection->getTableName('sales_order_grid');

            // Read persisted values from sales_order (source of truth)
            $select = $connection->select()
                ->from($orderTable, ['estimated_delivery_early', 'estimated_delivery_last'])
                ->where('entity_id = ?', $orderId)
                ->limit(1);

            $row = $connection->fetchRow($select);
            if (!$row) {
                return;
            }

            $estimatedDeliveryEarly = $row['estimated_delivery_early'] ?? null;
            $estimatedDeliveryLast = $row['estimated_delivery_last'] ?? null;

            // Only update grid if dates exist
            if ($estimatedDeliveryEarly || $estimatedDeliveryLast) {
                $updateData = [];

                if ($estimatedDeliveryEarly) {
                    $updateData['estimated_delivery_early'] = $estimatedDeliveryEarly;
                }

                if ($estimatedDeliveryLast) {
                    $updateData['estimated_delivery_last'] = $estimatedDeliveryLast;
                }

                if (!empty($updateData)) {
                    $affected = $connection->update(
                        $gridTable,
                        $updateData,
                        ['entity_id = ?' => $orderId]
                    );

                    // If grid row doesn't exist yet, create/refresh it, then update again.
                    if ((int)$affected === 0) {
                        $this->gridPool->refreshByOrderId($orderId);
                        $connection->update(
                            $gridTable,
                            $updateData,
                            ['entity_id = ?' => $orderId]
                        );
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->error(
                'Sync estimated dates to grid observer error: ' . $e->getMessage()
            );
        }
    }
}
