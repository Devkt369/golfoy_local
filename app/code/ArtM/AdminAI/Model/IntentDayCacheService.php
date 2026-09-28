<?php
namespace ArtM\AdminAI\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * Manages per-day raw data cache for date-range intents.
 *
 * Only "closed" calendar days (strictly before today) are persisted.
 * Today and future dates are always re-fetched from live DB so partial-day
 * data is never frozen in cache.
 *
 * Cache key = (intent_code, cache_date, param_hash).
 * param_hash = MD5 of all non-date, non-limit params (filters, attribute_sets, etc.)
 * so two queries with the same filters but different limits share per-day cache.
 */
class IntentDayCacheService
{
    /** @var ResourceConnection */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Return cached raw data for closed days (strictly before today) that fall
     * within [$fromDate, $toDate].  Result is keyed by 'Y-m-d' date string.
     *
     * @return array<string, array>
     */
    public function getCachedDays(
        string $intentCode,
        string $paramHash,
        string $fromDate,
        string $toDate
    ): array {
        // Closed days end at yesterday at the latest.
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($fromDate > $yesterday) {
            return [];   // entire range is today or future — nothing cacheable
        }

        $effectiveTo = min($toDate, $yesterday);

        try {
            $conn  = $this->getConnection();
            $table = $this->getTable();

            $rows = $conn->fetchAll(
                $conn->select()
                    ->from($table, ['cache_date', 'raw_data'])
                    ->where('intent_code = ?', $intentCode)
                    ->where('param_hash = ?', $paramHash)
                    ->where('cache_date >= ?', $fromDate)
                    ->where('cache_date <= ?', $effectiveTo)
            );

            $result = [];
            foreach ($rows as $row) {
                $raw = json_decode((string)$row['raw_data'], true);
                $result[(string)$row['cache_date']] = is_array($raw) ? $raw : [];
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Persist raw data for one closed day.  Uses INSERT … ON DUPLICATE KEY UPDATE
     * so repeated saves for already-cached days are safe no-ops.
     */
    public function saveDay(
        string $intentCode,
        string $date,
        string $paramHash,
        string $paramSnapshot,
        array  $rawData
    ): void {
        try {
            $this->getConnection()->insertOnDuplicate(
                $this->getTable(),
                [
                    'intent_code'     => $intentCode,
                    'cache_date'      => $date,
                    'param_hash'      => $paramHash,
                    'param_snapshot'  => $paramSnapshot,
                    'raw_data'        => json_encode($rawData, JSON_UNESCAPED_UNICODE),
                ],
                ['raw_data', 'param_snapshot']   // columns to update on conflict
            );
        } catch (\Throwable $e) {
            // Cache write failures must never break the response.
        }
    }

    /**
     * Generate every calendar day in [$fromDate, $toDate] as 'Y-m-d' strings.
     *
     * @return string[]
     */
    public function getDaysInRange(string $fromDate, string $toDate): array
    {
        $days    = [];
        $current = strtotime($fromDate);
        $end     = strtotime($toDate);

        if ($current === false || $end === false || $current > $end) {
            return [];
        }

        while ($current <= $end) {
            $days[]  = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $days;
    }

    /**
     * Build a stable hash for a set of non-date, non-limit parameters.
     * Keys are sorted so order does not affect the hash.
     */
    public function makeParamHash(array $params): string
    {
        ksort($params);
        return md5(json_encode($params));
    }

    /** Return today's date as 'Y-m-d'. */
    public function getToday(): string
    {
        return date('Y-m-d');
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /** @return \Magento\Framework\DB\Adapter\AdapterInterface */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }

    private function getTable(): string
    {
        return $this->resource->getTableName('artm_adminai_intent_day_cache');
    }
}
