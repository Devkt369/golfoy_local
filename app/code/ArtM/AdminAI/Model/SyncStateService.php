<?php
namespace ArtM\AdminAI\Model;

use ArtM\AdminAI\Api\SyncStateServiceInterface;
use Magento\Framework\App\ResourceConnection;

class SyncStateService implements SyncStateServiceInterface
{
    /** @var ResourceConnection */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function getState(string $syncCode): array
    {
        $syncCode = trim($syncCode);
        if ($syncCode === '') {
            return $this->buildEmptyState('');
        }

        try {
            $row = $this->getConnection()->fetchRow(
                $this->getConnection()->select()
                    ->from($this->getTable())
                    ->where('sync_code = ?', $syncCode)
                    ->limit(1)
            );

            if (!is_array($row) || empty($row)) {
                return $this->buildEmptyState($syncCode);
            }

            return [
                'sync_code' => (string)($row['sync_code'] ?? $syncCode),
                'last_synced_at' => $row['last_synced_at'] !== null ? (string)$row['last_synced_at'] : null,
                'last_entity_id' => $row['last_entity_id'] !== null ? (int)$row['last_entity_id'] : null,
                'status' => (string)($row['status'] ?? 'idle'),
                'message' => $row['message'] !== null ? (string)$row['message'] : null,
                'updated_at' => $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
            ];
        } catch (\Throwable $e) {
            return $this->buildEmptyState($syncCode);
        }
    }

    public function saveState(
        string $syncCode,
        ?string $lastSyncedAt,
        ?int $lastEntityId,
        string $status = 'idle',
        ?string $message = null
    ): void {
        $syncCode = trim($syncCode);
        if ($syncCode === '') {
            return;
        }

        $payload = [
            'sync_code' => $syncCode,
            'last_synced_at' => $lastSyncedAt,
            'last_entity_id' => $lastEntityId,
            'status' => $status !== '' ? $status : 'idle',
            'message' => $message,
        ];

        try {
            $this->getConnection()->insertOnDuplicate(
                $this->getTable(),
                $payload,
                ['last_synced_at', 'last_entity_id', 'status', 'message']
            );
        } catch (\Throwable $e) {
            // State writes should fail safely for callers.
        }
    }

    public function setStatus(string $syncCode, string $status, ?string $message = null): void
    {
        $current = $this->getState($syncCode);

        $this->saveState(
            $syncCode,
            $current['last_synced_at'],
            $current['last_entity_id'],
            $status !== '' ? $status : 'idle',
            $message
        );
    }

    /** @return \Magento\Framework\DB\Adapter\AdapterInterface */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }

    private function getTable(): string
    {
        return $this->resource->getTableName('artm_adminai_sync_state');
    }

    private function buildEmptyState(string $syncCode): array
    {
        return [
            'sync_code' => $syncCode,
            'last_synced_at' => null,
            'last_entity_id' => null,
            'status' => 'idle',
            'message' => null,
            'updated_at' => null,
        ];
    }
}
