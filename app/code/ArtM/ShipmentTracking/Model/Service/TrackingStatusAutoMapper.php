<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model\Service;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * When a status arrives that has NO mapping row, auto-inserts it with is_mapped=0.
 * If the row already exists (was inserted before), just bumps seen_count.
 * Admin sees it in the grid under "Not Mapped" and can fill in the display_label.
 */
class TrackingStatusAutoMapper
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly LoggerInterface    $logger
    ) {
    }

    public function insertIfNew(
        string $normalizedStatus,
        string $carrierName = '',
        string $trackingNumber = ''
    ): void {
        if ($normalizedStatus === '') {
            return;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table      = $this->resourceConnection->getTableName('artm_tracking_status_mapping');

            // First time seen  → INSERT with is_mapped=0, display_label=''
            // Seen again       → just bump seen_count (don't overwrite if admin already mapped it)
            $connection->query(
                "INSERT INTO `{$table}`
                    (`raw_status`, `display_label`, `carrier_name`, `is_active`, `is_mapped`, `seen_count`, `created_at`, `updated_at`)
                 VALUES
                    (:raw_status, '', :carrier_name, 1, 0, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    `seen_count` = `seen_count` + 1,
                    `updated_at` = NOW()",
                [
                    ':raw_status'   => $normalizedStatus,
                    ':carrier_name' => strtolower(trim($carrierName)),
                ]
            );
            // die($normalizedStatus);

        } catch (\Throwable $e) {
            $this->logger->error(
                '[ArtM EstimateDelivery] TrackingStatusAutoMapper error: ' . $e->getMessage(),
                ['status' => $normalizedStatus, 'carrier' => $carrierName]
            );
        }
    }
}
