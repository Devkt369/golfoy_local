<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model\Service;

use Magento\Framework\App\ResourceConnection;
use ArtM\ShipmentTracking\Logger\Logger;

/**
 * Records shipment tracks only after tracking confirms delivery and the order is marked delivered.
 */
class TrackingStatusLogger
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly Logger $logger
    ) {}

    public function logDeliveredOrder(
        int $orderId,
        string $incrementId
    ): void {
        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('artm_tracking_status_log');

            $connection->insertOnDuplicate(
                $table,
                [
                    'order_id'            => $incrementId,
                    'tracking_status'     => 'Delivered',
                    'marked_delivered_at' => date('Y-m-d H:i:s')
                ],
                [
                    'tracking_status',
                    'marked_delivered_at'
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Kept as a compatibility no-op. Delivered logs are not mapping records.
     */
    public function markMapped(string $rawStatus, string $carrierName = ''): void {}
}
