<?php
namespace ArtM\AdminAI\Model\Sync;

use ArtM\AdminAI\Api\SyncStateServiceInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class CustomerAnalyticsSync
{
    private const SYNC_CODE = 'customer_analytics';

    /** @var ResourceConnection */
    private $resource;

    /** @var SyncStateServiceInterface */
    private $syncStateService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ResourceConnection $resource,
        SyncStateServiceInterface $syncStateService,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->syncStateService = $syncStateService;
        $this->logger = $logger;
    }

    /**
     * @return int Number of upserted customer rows
     */
    public function execute(): int
    {
        $state = $this->syncStateService->getState(self::SYNC_CODE);
        $lastSyncedAt = $state['last_synced_at'] ?? null;

        $this->syncStateService->setStatus(self::SYNC_CODE, 'running', 'Customer analytics sync in progress');

        try {
            $conn = $this->resource->getConnection();
            $orderTable = $this->resource->getTableName('sales_order');
            $targetTable = $this->resource->getTableName('artm_adminai_customer_analytics');

            $pairs = $this->fetchAffectedCustomerStorePairs($conn, $orderTable, $lastSyncedAt);
            if (empty($pairs)) {
                $now = gmdate('Y-m-d H:i:s');
                $this->syncStateService->saveState(self::SYNC_CODE, $now, null, 'idle', 'No new customer analytics rows to sync');
                return 0;
            }

            $upserted = 0;
            foreach ($pairs as $pair) {
                $customerId = (int)$pair['customer_id'];
                $storeId = (int)$pair['store_id'];

                $metrics = $this->fetchCustomerMetrics($conn, $orderTable, $customerId, $storeId);
                $lastOrderAt = $metrics['last_order_at'];
                $orders90d = (int)$metrics['orders_count_90d'];
                $revenue90d = (float)$metrics['revenue_90d'];
                $discountRatio = isset($metrics['discount_ratio']) ? (float)$metrics['discount_ratio'] : null;
                $aov90d = $orders90d > 0 ? ($revenue90d / $orders90d) : 0.0;
                $daysSinceLastOrder = $this->calculateDaysSince($lastOrderAt);

                $riskScore = $this->calculateRiskScore($daysSinceLastOrder, $orders90d, $revenue90d);
                $riskLevel = $this->deriveRiskLevel($riskScore);

                $conn->insertOnDuplicate(
                    $targetTable,
                    [
                        'customer_id' => $customerId,
                        'store_id' => $storeId,
                        'last_order_at' => $lastOrderAt,
                        'orders_count_90d' => $orders90d,
                        'revenue_90d' => $revenue90d,
                        'avg_order_value_90d' => $aov90d,
                        'discount_ratio' => $discountRatio,
                        'days_since_last_order' => $daysSinceLastOrder,
                        'risk_score' => $riskScore,
                        'risk_level' => $riskLevel,
                    ],
                    [
                        'last_order_at',
                        'orders_count_90d',
                        'revenue_90d',
                        'avg_order_value_90d',
                        'discount_ratio',
                        'days_since_last_order',
                        'risk_score',
                        'risk_level'
                    ]
                );
                $upserted++;
            }

            $watermark = $this->fetchWatermark($conn, $orderTable, $lastSyncedAt);
            $this->syncStateService->saveState(
                self::SYNC_CODE,
                $watermark['last_synced_at'] ?? gmdate('Y-m-d H:i:s'),
                $watermark['last_entity_id'] ?? null,
                'idle',
                'Customer analytics sync complete: ' . $upserted . ' rows'
            );

            return $upserted;
        } catch (\Throwable $e) {
            $this->syncStateService->setStatus(self::SYNC_CODE, 'failed', 'Customer analytics sync failed');
            $this->logger->error('CustomerAnalyticsSync failed', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $orderTable
     * @param string|null $lastSyncedAt
     * @return array<int,array{customer_id:int,store_id:int}>
     */
    private function fetchAffectedCustomerStorePairs($conn, string $orderTable, ?string $lastSyncedAt): array
    {
        $select = $conn->select()
            ->from(
                ['o' => $orderTable],
                [
                    'customer_id' => 'o.customer_id',
                    'store_id' => 'o.store_id'
                ]
            )
            ->where('o.customer_id IS NOT NULL')
            ->where('o.state NOT IN (?)', ['canceled', 'closed'])
            ->group(['o.customer_id', 'o.store_id']);

        if ($lastSyncedAt !== null && $lastSyncedAt !== '') {
            $select->where('o.created_at > ?', $lastSyncedAt);
        }

        $rows = $conn->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'customer_id' => (int)$row['customer_id'],
                'store_id' => (int)$row['store_id'],
            ];
        }

        return $result;
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $orderTable
     * @param int $customerId
     * @param int $storeId
    * @return array{last_order_at:?string,orders_count_90d:int,revenue_90d:float,discount_ratio:?float}
     */
    private function fetchCustomerMetrics($conn, string $orderTable, int $customerId, int $storeId): array
    {
        $allTimeLastOrderAt = $conn->fetchOne(
            $conn->select()
                ->from(['o' => $orderTable], [new \Zend_Db_Expr('MAX(o.created_at)')])
                ->where('o.customer_id = ?', $customerId)
                ->where('o.store_id = ?', $storeId)
                ->where('o.state NOT IN (?)', ['canceled', 'closed'])
        );

        $row90d = $conn->fetchRow(
            $conn->select()
                ->from(
                    ['o' => $orderTable],
                    [
                        'orders_count_90d' => new \Zend_Db_Expr('COUNT(*)'),
                        'revenue_90d' => new \Zend_Db_Expr('SUM(o.base_grand_total)'),
                        'discount_orders_90d' => new \Zend_Db_Expr('SUM(CASE WHEN ABS(o.discount_amount) > 0.0001 THEN 1 ELSE 0 END)')
                    ]
                )
                ->where('o.customer_id = ?', $customerId)
                ->where('o.store_id = ?', $storeId)
                ->where('o.state NOT IN (?)', ['canceled', 'closed'])
                ->where('o.created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 90 DAY)')
        );

        $orders90d = isset($row90d['orders_count_90d']) ? (int)$row90d['orders_count_90d'] : 0;
        $discountOrders90d = isset($row90d['discount_orders_90d']) ? (int)$row90d['discount_orders_90d'] : 0;

        return [
            'last_order_at' => $allTimeLastOrderAt !== false && $allTimeLastOrderAt !== null
                ? (string)$allTimeLastOrderAt
                : null,
            'orders_count_90d' => $orders90d,
            'revenue_90d' => isset($row90d['revenue_90d']) && $row90d['revenue_90d'] !== null
                ? (float)$row90d['revenue_90d']
                : 0.0,
            'discount_ratio' => $orders90d > 0 ? round($discountOrders90d / $orders90d, 2) : null,
        ];
    }

    private function calculateDaysSince(?string $dateTime): ?int
    {
        if ($dateTime === null || $dateTime === '') {
            return null;
        }

        $lastTs = strtotime($dateTime);
        if ($lastTs === false) {
            return null;
        }

        $seconds = time() - $lastTs;
        if ($seconds < 0) {
            return 0;
        }

        return (int)floor($seconds / 86400);
    }

    private function calculateRiskScore(?int $daysSinceLastOrder, int $orders90d, float $revenue90d): float
    {
        if ($daysSinceLastOrder === null) {
            return 100.0;
        }

        $recencyScore = min(100.0, $daysSinceLastOrder * 1.2);
        $frequencyReduction = min(40.0, $orders90d * 4.0);
        $revenueReduction = min(30.0, $revenue90d / 500.0);

        $score = $recencyScore - $frequencyReduction - $revenueReduction;
        if ($score < 0.0) {
            $score = 0.0;
        }
        if ($score > 100.0) {
            $score = 100.0;
        }

        return round($score, 2);
    }

    private function deriveRiskLevel(float $score): string
    {
        if ($score >= 70.0) {
            return 'high';
        }
        if ($score >= 40.0) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $orderTable
     * @param string|null $lastSyncedAt
     * @return array{last_synced_at:?string,last_entity_id:?int}
     */
    private function fetchWatermark($conn, string $orderTable, ?string $lastSyncedAt): array
    {
        $select = $conn->select()
            ->from(
                ['o' => $orderTable],
                [
                    'last_synced_at' => new \Zend_Db_Expr('MAX(o.created_at)'),
                    'last_entity_id' => new \Zend_Db_Expr('MAX(o.entity_id)')
                ]
            )
            ->where('o.state NOT IN (?)', ['canceled', 'closed']);

        if ($lastSyncedAt !== null && $lastSyncedAt !== '') {
            $select->where('o.created_at > ?', $lastSyncedAt);
        }

        $row = $conn->fetchRow($select);
        if (!is_array($row)) {
            return ['last_synced_at' => null, 'last_entity_id' => null];
        }

        return [
            'last_synced_at' => $row['last_synced_at'] !== null ? (string)$row['last_synced_at'] : null,
            'last_entity_id' => $row['last_entity_id'] !== null ? (int)$row['last_entity_id'] : null,
        ];
    }
}
