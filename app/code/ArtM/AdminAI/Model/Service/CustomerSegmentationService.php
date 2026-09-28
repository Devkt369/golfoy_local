<?php
namespace ArtM\AdminAI\Model\Service;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class CustomerSegmentationService
{
    private const SEGMENT_NEW_CUSTOMER = 'NEW_CUSTOMER';
    private const SEGMENT_FREQUENT_BUYER = 'FREQUENT_BUYER';
    private const SEGMENT_HIGH_VALUE_CUSTOMER = 'HIGH_VALUE_CUSTOMER';
    private const SEGMENT_DISCOUNT_HUNTER = 'DISCOUNT_HUNTER';
    private const SEGMENT_NEW_ARRIVAL_BUYER = 'NEW_ARRIVAL_BUYER';
    private const SEGMENT_CATEGORY_LOYAL = 'CATEGORY_LOYAL';
    private const SEGMENT_AT_RISK_CUSTOMER = 'AT_RISK_CUSTOMER';
    private const SEGMENT_INACTIVE_CUSTOMER = 'INACTIVE_CUSTOMER';

    /** @var ResourceConnection */
    private $resource;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Build deterministic customer segments from analytics tables only.
     *
     * @param array<int,mixed> $customerIds
     * @return int Number of segment rows inserted
     */
    public function processCustomers(array $customerIds): int
    {
        $ids = $this->normalizeIds($customerIds);
        if (empty($ids)) {
            return 0;
        }

        $conn = $this->resource->getConnection();
        $customerAnalyticsTable = $this->resource->getTableName('artm_adminai_customer_analytics');
        $productAnalyticsTable = $this->resource->getTableName('artm_adminai_product_analytics_day');
        $salesAggregateTable = $this->resource->getTableName('artm_adminai_sales_aggregate_day');
        $segmentsTable = $this->resource->getTableName('artm_adminai_customer_segments');

        $globalAvgRevenue90d = (float)$conn->fetchOne(
            $conn->select()->from(
                $customerAnalyticsTable,
                [new \Zend_Db_Expr('COALESCE(AVG(revenue_90d), 0)')]
            )
        );

        $salesSignalsByStore = $this->fetchSalesSignalsByStore($conn, $salesAggregateTable);
        $productColumns = $this->getTableColumns($conn, $productAnalyticsTable);
        $customerColumns = $this->getTableColumns($conn, $customerAnalyticsTable);

        $customerRows = $conn->fetchAll(
            $conn->select()
                ->from(['ca' => $customerAnalyticsTable], '*')
                ->where('ca.customer_id IN (?)', $ids)
        );

        if (empty($customerRows)) {
            $conn->delete($segmentsTable, ['customer_id IN (?)' => $ids]);
            return 0;
        }

        $conn->delete($segmentsTable, ['customer_id IN (?)' => $ids]);

        $insertRows = [];
        foreach ($customerRows as $row) {
            $storeId = isset($row['store_id']) ? (int)$row['store_id'] : null;
            $segments = $this->resolveSegments(
                $row,
                $globalAvgRevenue90d,
                $salesSignalsByStore[$storeId] ?? ['avg_orders_per_day' => 0.0],
                $customerColumns,
                $productColumns,
                $conn,
                $productAnalyticsTable
            );

            foreach ($segments as $segment) {
                $insertRows[] = [
                    'customer_id' => (int)$row['customer_id'],
                    'store_id' => $storeId,
                    'segment_code' => $segment['segment_code'],
                    'segment_score' => $segment['segment_score'],
                    'meta' => $segment['meta'],
                ];
            }
        }

        if (empty($insertRows)) {
            return 0;
        }

        $conn->insertMultiple($segmentsTable, $insertRows);

        return count($insertRows);
    }

    /**
     * @param array<string,mixed> $row
     * @param float $globalAvgRevenue90d
     * @param array<string,mixed> $storeSignals
     * @param array<int,string> $customerColumns
     * @param array<int,string> $productColumns
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $productAnalyticsTable
     * @return array<int,array{segment_code:string,segment_score:float,meta:?string}>
     */
    private function resolveSegments(
        array $row,
        float $globalAvgRevenue90d,
        array $storeSignals,
        array $customerColumns,
        array $productColumns,
        $conn,
        string $productAnalyticsTable
    ): array {
        $segments = [];

        $customerId = (int)($row['customer_id'] ?? 0);
        $storeId = isset($row['store_id']) ? (int)$row['store_id'] : null;
        $orders90d = (int)($row['orders_count_90d'] ?? 0);
        $revenue90d = (float)($row['revenue_90d'] ?? 0.0);
        $daysSinceLastOrder = isset($row['days_since_last_order']) ? (int)$row['days_since_last_order'] : null;
        $discountRatio = isset($row['discount_ratio']) && $row['discount_ratio'] !== null ? (float)$row['discount_ratio'] : null;

        if ($daysSinceLastOrder !== null && $daysSinceLastOrder <= 30 && $orders90d === 1) {
            $segments[] = $this->segment(self::SEGMENT_NEW_CUSTOMER, 82.0, null);
        }

        if ($orders90d >= 5) {
            $score = min(99.0, 70.0 + ($orders90d * 4.0));
            $segments[] = $this->segment(self::SEGMENT_FREQUENT_BUYER, $score, null);
        }

        if ($globalAvgRevenue90d > 0.0 && $revenue90d >= (2.0 * $globalAvgRevenue90d)) {
            $score = min(99.0, 70.0 + (($revenue90d / max(1.0, $globalAvgRevenue90d)) * 4.0));
            $segments[] = $this->segment(self::SEGMENT_HIGH_VALUE_CUSTOMER, $score, $this->toJson([
                'revenue_90d' => round($revenue90d, 2),
                'global_avg_revenue_90d' => round($globalAvgRevenue90d, 2),
            ]));
        }

        if ($discountRatio !== null && $discountRatio >= 0.60) {
            $segments[] = $this->segment(self::SEGMENT_DISCOUNT_HUNTER, round($discountRatio * 100, 2), $this->toJson([
                'discount_ratio' => round($discountRatio, 2),
            ]));
        }

        $newArrivalSignal = $this->resolveNewArrivalSignal(
            $row,
            $storeSignals,
            $productColumns,
            $conn,
            $productAnalyticsTable,
            $storeId
        );
        if ($newArrivalSignal['is_match']) {
            $segments[] = $this->segment(
                self::SEGMENT_NEW_ARRIVAL_BUYER,
                $newArrivalSignal['score'],
                $this->toJson($newArrivalSignal['meta'])
            );
        }

        $categorySignal = $this->resolveCategoryLoyalSignal($row, $customerColumns);
        if ($categorySignal['is_match']) {
            $segments[] = $this->segment(
                self::SEGMENT_CATEGORY_LOYAL,
                $categorySignal['score'],
                $this->toJson($categorySignal['meta'])
            );
        }

        $avgPurchaseCycle = $orders90d > 0 ? (90.0 / $orders90d) : 90.0;
        if ($daysSinceLastOrder !== null && $daysSinceLastOrder > ($avgPurchaseCycle * 1.5)) {
            $segments[] = $this->segment(self::SEGMENT_AT_RISK_CUSTOMER, min(99.0, round($daysSinceLastOrder, 2)), $this->toJson([
                'days_since_last_order' => $daysSinceLastOrder,
                'avg_purchase_cycle' => round($avgPurchaseCycle, 2),
            ]));
        }

        if ($daysSinceLastOrder !== null && $daysSinceLastOrder >= 90) {
            $segments[] = $this->segment(self::SEGMENT_INACTIVE_CUSTOMER, min(99.0, round($daysSinceLastOrder, 2)), null);
        }

        if (empty($segments) && $customerId > 0) {
            $this->logger->info('CustomerSegmentationService: no segment matched', [
                'customer_id' => $customerId,
                'store_id' => $storeId,
            ]);
        }

        return $segments;
    }

    /**
     * @param array<string,mixed> $row
     * @param array<string,mixed> $storeSignals
     * @param array<int,string> $productColumns
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $productAnalyticsTable
     * @param int|null $storeId
     * @return array{is_match:bool,score:float,meta:array<string,mixed>}
     */
    private function resolveNewArrivalSignal(
        array $row,
        array $storeSignals,
        array $productColumns,
        $conn,
        string $productAnalyticsTable,
        ?int $storeId
    ): array {
        if (in_array('new_arrival_ratio', $this->arrayToLower($row), true)) {
            $ratio = isset($row['new_arrival_ratio']) ? (float)$row['new_arrival_ratio'] : 0.0;
            return [
                'is_match' => $ratio >= 0.30,
                'score' => round($ratio * 100, 2),
                'meta' => ['new_arrival_ratio' => round($ratio, 2)],
            ];
        }

        if (in_array('is_new_arrival', $productColumns, true)) {
            $select = $conn->select()->from(
                ['pa' => $productAnalyticsTable],
                [
                    'new_arrival_qty' => new \Zend_Db_Expr('COALESCE(SUM(CASE WHEN pa.is_new_arrival = 1 THEN pa.qty_sold ELSE 0 END), 0)'),
                    'total_qty' => new \Zend_Db_Expr('COALESCE(SUM(pa.qty_sold), 0)')
                ]
            )->where('pa.aggregate_date >= DATE_SUB(UTC_DATE(), INTERVAL 60 DAY)');

            if ($storeId !== null) {
                $select->where('pa.store_id = ?', $storeId);
            }

            $agg = $conn->fetchRow($select);
            $newArrivalQty = isset($agg['new_arrival_qty']) ? (float)$agg['new_arrival_qty'] : 0.0;
            $totalQty = isset($agg['total_qty']) ? (float)$agg['total_qty'] : 0.0;
            $ratio = $totalQty > 0.0 ? ($newArrivalQty / $totalQty) : 0.0;

            $orders90d = (int)($row['orders_count_90d'] ?? 0);
            $daysSince = isset($row['days_since_last_order']) ? (int)$row['days_since_last_order'] : null;

            return [
                'is_match' => $ratio >= 0.30 && $orders90d > 0 && $daysSince !== null && $daysSince <= 60,
                'score' => round($ratio * 100, 2),
                'meta' => [
                    'store_new_arrival_sales_ratio' => round($ratio, 2),
                    'signal' => 'store_aggregate_proxy',
                ],
            ];
        }

        $orders90d = (int)($row['orders_count_90d'] ?? 0);
        $daysSince = isset($row['days_since_last_order']) ? (int)$row['days_since_last_order'] : null;
        $avgOrdersPerDay = isset($storeSignals['avg_orders_per_day']) ? (float)$storeSignals['avg_orders_per_day'] : 0.0;

        return [
            'is_match' => $orders90d >= 2 && $daysSince !== null && $daysSince <= 60 && $avgOrdersPerDay > 0.0,
            'score' => min(95.0, round(($orders90d / 9.0) * 100, 2)),
            'meta' => [
                'signal' => 'fallback_recent_order_pattern',
                'avg_orders_per_day' => round($avgOrdersPerDay, 4),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,string> $customerColumns
     * @return array{is_match:bool,score:float,meta:array<string,mixed>}
     */
    private function resolveCategoryLoyalSignal(array $row, array $customerColumns): array
    {
        if (in_array('dominant_category_ratio', $customerColumns, true)) {
            $ratio = isset($row['dominant_category_ratio']) ? (float)$row['dominant_category_ratio'] : 0.0;
            $categoryId = isset($row['dominant_category_id']) ? (int)$row['dominant_category_id'] : null;

            return [
                'is_match' => $ratio > 0.70 && $categoryId !== null && $categoryId > 0,
                'score' => round($ratio * 100, 2),
                'meta' => [
                    'category_id' => $categoryId,
                    'category_ratio' => round($ratio, 2),
                ],
            ];
        }

        return [
            'is_match' => false,
            'score' => 0.0,
            'meta' => [
                'signal' => 'insufficient_category_signal',
            ],
        ];
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $salesAggregateTable
     * @return array<int,array{avg_orders_per_day:float}>
     */
    private function fetchSalesSignalsByStore($conn, string $salesAggregateTable): array
    {
        $rows = $conn->fetchAll(
            $conn->select()
                ->from(
                    ['sa' => $salesAggregateTable],
                    [
                        'store_id' => 'sa.store_id',
                        'avg_orders_per_day' => new \Zend_Db_Expr('COALESCE(AVG(sa.orders_count), 0)')
                    ]
                )
                ->where('sa.aggregate_date >= DATE_SUB(UTC_DATE(), INTERVAL 90 DAY)')
                ->group('sa.store_id')
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['store_id']] = [
                'avg_orders_per_day' => (float)$row['avg_orders_per_day'],
            ];
        }

        return $result;
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $table
     * @return array<int,string>
     */
    private function getTableColumns($conn, string $table): array
    {
        try {
            $description = $conn->describeTable($table);
            if (!is_array($description)) {
                return [];
            }

            return array_keys($description);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<int,mixed> $customerIds
     * @return array<int,int>
     */
    private function normalizeIds(array $customerIds): array
    {
        $normalized = [];
        foreach ($customerIds as $customerId) {
            $id = (int)$customerId;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param array<string,mixed> $data
     * @return array{segment_code:string,segment_score:float,meta:?string}
     */
    private function segment(string $segmentCode, float $segmentScore, ?string $meta): array
    {
        return [
            'segment_code' => $segmentCode,
            'segment_score' => round($segmentScore, 2),
            'meta' => $meta,
        ];
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
     * @param array<string,mixed> $value
     * @return array<int,string>
     */
    private function arrayToLower(array $value): array
    {
        return array_map('strtolower', array_keys($value));
    }
}
