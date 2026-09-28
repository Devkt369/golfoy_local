<?php
namespace ArtM\AdminAI\Model;

use ArtM\AdminAI\Api\AnalyticsCacheServiceInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;

class AnalyticsCacheService implements AnalyticsCacheServiceInterface
{
    /** @var ResourceConnection */
    private $resource;

    /** @var DateTime */
    private $dateTime;

    public function __construct(
        ResourceConnection $resource,
        DateTime $dateTime
    ) {
        $this->resource = $resource;
        $this->dateTime = $dateTime;
    }

    public function get(string $cacheKey, ?string $expectedDataVersion = null): ?array
    {
        $cacheKey = trim($cacheKey);
        if ($cacheKey === '') {
            return null;
        }

        try {
            $select = $this->getConnection()->select()
                ->from($this->getTable(), ['payload', 'data_version'])
                ->where('cache_key = ?', $cacheKey)
                ->where('expires_at > ?', $this->nowUtc())
                ->limit(1);

            if ($expectedDataVersion !== null && $expectedDataVersion !== '') {
                $select->where('data_version = ?', $expectedDataVersion);
            }

            $row = $this->getConnection()->fetchRow($select);
            if (!is_array($row) || empty($row['payload'])) {
                return null;
            }

            $decoded = json_decode((string)$row['payload'], true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function set(
        string $cacheKey,
        string $intentCode,
        array $payload,
        string $dataVersion,
        int $ttlSeconds
    ): void {
        $cacheKey = trim($cacheKey);
        $intentCode = trim($intentCode);
        $dataVersion = trim($dataVersion);

        if ($cacheKey === '' || $intentCode === '' || $dataVersion === '') {
            return;
        }

        $ttlSeconds = max(1, $ttlSeconds);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $ttlSeconds . ' seconds', strtotime($this->nowUtc())));

        try {
            $this->getConnection()->insertOnDuplicate(
                $this->getTable(),
                [
                    'cache_key' => $cacheKey,
                    'intent_code' => $intentCode,
                    'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'data_version' => $dataVersion,
                    'expires_at' => $expiresAt,
                ],
                ['intent_code', 'payload', 'data_version', 'expires_at']
            );
        } catch (\Throwable $e) {
            // Cache writes should fail safely for callers.
        }
    }

    public function delete(string $cacheKey): void
    {
        $cacheKey = trim($cacheKey);
        if ($cacheKey === '') {
            return;
        }

        try {
            $this->getConnection()->delete($this->getTable(), ['cache_key = ?' => $cacheKey]);
        } catch (\Throwable $e) {
            // Ignore cache delete failures.
        }
    }

    public function deleteExpired(): int
    {
        try {
            return (int)$this->getConnection()->delete(
                $this->getTable(),
                ['expires_at <= ?' => $this->nowUtc()]
            );
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** @return \Magento\Framework\DB\Adapter\AdapterInterface */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }

    private function getTable(): string
    {
        return $this->resource->getTableName('artm_adminai_analytics_cache');
    }

    private function nowUtc(): string
    {
        return $this->dateTime->gmtDate('Y-m-d H:i:s');
    }
}
