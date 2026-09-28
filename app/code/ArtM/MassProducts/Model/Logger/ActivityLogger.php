<?php
namespace ArtM\MassProducts\Model\Logger;

use Magento\Framework\App\ResourceConnection;

class ActivityLogger
{
    private const LOG_TIMEZONE = 'Asia/Kolkata';

    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function log(array $data): void
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_massproduct_activity_log');
            if (!$connection->isTableExists($table)) {
                return;
            }

            $now = (new \DateTime('now', new \DateTimeZone(self::LOG_TIMEZONE)))->format('Y-m-d H:i:s');
            $payload = [
                'action_type' => (string)($data['action_type'] ?? 'unknown'),
                'sku' => $this->nullIfEmpty($data['sku'] ?? null),
                'draft_id' => $this->nullIfZero((int)($data['draft_id'] ?? 0)),
                'upload_id' => $this->nullIfZero((int)($data['upload_id'] ?? 0)),
                'sheet_name' => $this->nullIfEmpty($data['sheet_name'] ?? null),
                'product_count' => $this->nullIfZero((int)($data['product_count'] ?? 0)),
                'admin_user_id' => $this->nullIfZero((int)($data['admin_user_id'] ?? 0)),
                'admin_username' => $this->nullIfEmpty($data['admin_username'] ?? null),
                'details' => $this->nullIfEmpty($data['details'] ?? null),
                'comment' => $this->nullIfEmpty($data['comment'] ?? null),
                'occurred_at' => (string)($data['occurred_at'] ?? $now),
                'created_at' => $now,
            ];

            $connection->insert($table, $payload);
        } catch (\Throwable $e) {
            // Never block core flow due to logging failures.
        }
    }

    private function nullIfEmpty($value)
    {
        $v = trim((string)$value);
        return $v === '' ? null : $v;
    }

    private function nullIfZero(int $value)
    {
        return $value > 0 ? $value : null;
    }
}
