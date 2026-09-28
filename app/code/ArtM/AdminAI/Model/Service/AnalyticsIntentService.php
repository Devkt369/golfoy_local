<?php
namespace ArtM\AdminAI\Model\Service;

use ArtM\AdminAI\Api\Service\AnalyticsCacheServiceInterface;
use ArtM\AdminAI\Model\Sync\CustomerSegmentationSync;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class AnalyticsIntentService
{
    private const ALLOWED_SEGMENTS = [
        'NEW_CUSTOMER',
        'FREQUENT_BUYER',
        'HIGH_VALUE_CUSTOMER',
        'DISCOUNT_HUNTER',
        'NEW_ARRIVAL_BUYER',
        'CATEGORY_LOYAL',
        'AT_RISK_CUSTOMER',
        'INACTIVE_CUSTOMER',
    ];

    /** @var AnalyticsCacheServiceInterface */
    private $analyticsCacheService;

    /** @var ResourceConnection */
    private $resource;

    /** @var CustomerSegmentationSync */
    private $customerSegmentationSync;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        AnalyticsCacheServiceInterface $analyticsCacheService,
        ResourceConnection $resource,
        CustomerSegmentationSync $customerSegmentationSync,
        LoggerInterface $logger
    ) {
        $this->analyticsCacheService = $analyticsCacheService;
        $this->resource = $resource;
        $this->customerSegmentationSync = $customerSegmentationSync;
        $this->logger = $logger;
    }

    /**
     * Generic cache-enabled execution wrapper for analytics intents.
     *
     * @param string $sessionId
     * @param string $intent
     * @param array $params
     * @param string $source gemini|manual
     * @param callable $resolver returns array result
     * @return array
     */
    public function execute(
        string $sessionId,
        string $intent,
        array $params,
        string $source,
        callable $resolver
    ): array {
        $cached = $this->analyticsCacheService->getCachedResult($intent, $params);
        if ($cached !== null) {
            return $cached;
        }

        $result = $resolver($params);
        if (!is_array($result)) {
            $result = ['result' => $result];
        }

        $this->analyticsCacheService->saveCache($intent, $params, $result);
        $this->analyticsCacheService->saveChatResult($sessionId, $intent, $params, $result, $source);

        return $result;
    }

    /**
     * @param array<string,mixed> $params
     */
    public function processCustomerSegmentList(array $params): string
    {
        $segmentCode = strtoupper(trim((string)($params['segment_code'] ?? '')));
        $limit = (int)($params['limit'] ?? 20);
        $storeId = isset($params['store_id']) && $params['store_id'] !== '' ? (int)$params['store_id'] : null;

        $limit = $limit > 0 ? min($limit, 200) : 20;

        if ($segmentCode !== '' && !in_array($segmentCode, self::ALLOWED_SEGMENTS, true)) {
            return 'Invalid segment_code. Allowed values: ' . implode(', ', self::ALLOWED_SEGMENTS) . '.';
        }

        $baseParams = [
            'segment_code' => $segmentCode !== '' ? $segmentCode : null,
            'limit' => $limit,
            'store_id' => $storeId,
        ];

        $cacheParams = $baseParams;
        $cacheParams['cache_key'] = $this->buildSegmentCacheKey($baseParams);

        $cached = $this->analyticsCacheService->getCachedResult('customer_segment_list', $cacheParams);
        if ($cached !== null) {
            $rows = isset($cached['rows']) && is_array($cached['rows']) ? $cached['rows'] : [];
            return $this->formatSegmentResponse($rows, $baseParams, true);
        }

        try {
            $rows = $this->fetchSegmentRows($segmentCode, $limit, $storeId);
            if (empty($rows)) {
                $rows = $this->triggerInlineSegmentationIfNeeded($rows, $segmentCode, $limit, $storeId);
            }
            if (empty($rows)) {
                return $this->buildNoSegmentsMessage($baseParams);
            }
            $this->analyticsCacheService->saveCache('customer_segment_list', $cacheParams, ['rows' => $rows]);
            return $this->formatSegmentResponse($rows, $baseParams, false);
        } catch (\Throwable $e) {
            $this->logger->error('AnalyticsIntentService processCustomerSegmentList failed', [
                'exception' => $e,
                'params' => $params,
            ]);

            return 'Unable to fetch customer segment list right now.';
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchSegmentRows(string $segmentCode, int $limit, ?int $storeId): array
    {
        $conn = $this->resource->getConnection();
        $segmentsTable = $this->resource->getTableName('artm_adminai_customer_segments');

        $select = $conn->select()
            ->from(
                ['s' => $segmentsTable],
                [
                    'customer_id' => 's.customer_id',
                    'segment_code' => 's.segment_code',
                    'segment_score' => 's.segment_score',
                    'updated_at' => 's.updated_at',
                ]
            )
            ->order('s.updated_at DESC')
            ->limit($limit);

        if ($segmentCode !== '') {
            $select->where('s.segment_code = ?', $segmentCode);
        }
        if ($storeId !== null && $storeId > 0) {
            $select->where('s.store_id = ?', $storeId);
        }

        $rows = $conn->fetchAll($select);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildSegmentCacheKey(array $params): string
    {
        $payload = [
            'segment_code' => $params['segment_code'] ?? null,
            'limit' => $params['limit'] ?? null,
            'store_id' => $params['store_id'] ?? null,
        ];

        $hash = md5($this->toJson($payload));
        $storePart = $payload['store_id'] !== null ? (string)$payload['store_id'] : 'all';

        return 'analytics:customer_segments:' . $storePart . ':' . $hash;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array<string,mixed> $params
     */
    private function formatSegmentResponse(array $rows, array $params, bool $fromCache): string
    {
        if (empty($rows)) {
            return 'No customer segments found for the selected filters.';
        }

        $lines = [];
        $lines[] = 'Customer Segment List' . ($fromCache ? ' (cached)' : '') . ':';
        $lines[] = 'Filters: segment_code=' . ($params['segment_code'] ?? 'ALL')
            . ', store_id=' . (($params['store_id'] ?? null) !== null ? (string)$params['store_id'] : 'ALL')
            . ', limit=' . (string)$params['limit'];
        $lines[] = '';

        foreach ($rows as $row) {
            $lines[] = '• Customer #' . (int)$row['customer_id']
                . ' | ' . (string)$row['segment_code']
                . ' | Score: ' . ($row['segment_score'] !== null ? (string)$row['segment_score'] : 'n/a')
                . ' | Updated: ' . (string)$row['updated_at'];
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string,mixed> $value
     */
    private function toJson(array $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '{}';
    }

    /**
     * If the segments table is empty but customer_analytics has data, run the
     * segmentation sync inline so the first request does not require cron.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function triggerInlineSegmentationIfNeeded(array $rows, string $segmentCode, int $limit, ?int $storeId): array
    {
        try {
            $conn = $this->resource->getConnection();
            $analyticsRows = (int)$conn->fetchOne(
                'SELECT COUNT(*) FROM ' . $this->resource->getTableName('artm_adminai_customer_analytics')
            );
            if ($analyticsRows === 0) {
                return $rows;
            }
            $this->logger->info('AnalyticsIntentService: segments empty, triggering inline segmentation sync');
            $this->customerSegmentationSync->execute();
            return $this->fetchSegmentRows($segmentCode, $limit, $storeId);
        } catch (\Throwable $e) {
            $this->logger->error('AnalyticsIntentService inline segmentation sync failed', ['exception' => $e]);
            return $rows;
        }
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildNoSegmentsMessage(array $params): string
    {
        try {
            $conn = $this->resource->getConnection();
            $segmentsTable = $this->resource->getTableName('artm_adminai_customer_segments');
            $customerAnalyticsTable = $this->resource->getTableName('artm_adminai_customer_analytics');
            $syncStateTable = $this->resource->getTableName('artm_adminai_sync_state');

            $segmentRows = (int)$conn->fetchOne('SELECT COUNT(*) FROM ' . $segmentsTable);
            $analyticsRows = (int)$conn->fetchOne('SELECT COUNT(*) FROM ' . $customerAnalyticsTable);

            $syncRow = $conn->fetchRow(
                $conn->select()
                    ->from($syncStateTable, ['last_synced_at', 'status', 'message', 'updated_at'])
                    ->where('sync_code = ?', 'customer_segmentation')
                    ->limit(1)
            );

            $lines = [];
            $lines[] = 'No customer segments found for the selected filters.';
            $lines[] = 'Filters: segment_code=' . ($params['segment_code'] ?? 'ALL')
                . ', store_id=' . (($params['store_id'] ?? null) !== null ? (string)$params['store_id'] : 'ALL')
                . ', limit=' . (string)($params['limit'] ?? 20);
            $lines[] = '';
            $lines[] = 'Data status:';
            $lines[] = '- customer_analytics rows: ' . $analyticsRows;
            $lines[] = '- customer_segments rows: ' . $segmentRows;

            if (is_array($syncRow) && !empty($syncRow)) {
                $lines[] = '- segmentation sync status: ' . (string)($syncRow['status'] ?? 'unknown');
                $lines[] = '- segmentation last_synced_at: ' . (string)($syncRow['last_synced_at'] ?? 'null');
                if (!empty($syncRow['message'])) {
                    $lines[] = '- sync message: ' . (string)$syncRow['message'];
                }
            } else {
                $lines[] = '- segmentation sync status: no sync_state record found';
            }

            $lines[] = '';
            $lines[] = 'Next step: run customer analytics sync and customer segmentation cron, then retry.';

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->warning('AnalyticsIntentService buildNoSegmentsMessage failed', ['exception' => $e]);
            return 'No customer segments found for the selected filters. Run customer analytics + segmentation sync and retry.';
        }
    }
}
