<?php
namespace ArtM\AdminAI\Model\ResourceModel\Dashboard;

use Magento\Framework\App\ResourceConnection;

class OverviewData
{
    /** @var ResourceConnection */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function fetch(array $filters): array
    {
        $conn = $this->resource->getConnection();
        $salesTable = $this->resource->getTableName('artm_adminai_sales_aggregate_day');
        $customerTable = $this->resource->getTableName('artm_adminai_customer_analytics');
        $segmentTable = $this->resource->getTableName('artm_adminai_customer_segments');
        $notificationTable = $this->resource->getTableName('artm_adminai_notification_queue');

        $fromDate = (string)($filters['from_date'] ?? gmdate('Y-m-d', strtotime('-29 days')));
        $toDate = (string)($filters['to_date'] ?? gmdate('Y-m-d'));
        $range = (string)($filters['range'] ?? '30d');

        $salesSummary = $conn->select()
            ->from(
                ['s' => $salesTable],
                [
                    'total_revenue' => 'COALESCE(SUM(s.revenue_total), 0)',
                    'orders_count' => 'COALESCE(SUM(s.orders_count), 0)',
                ]
            )
            ->where('s.aggregate_date >= ?', $fromDate)
            ->where('s.aggregate_date <= ?', $toDate);

        $salesTrend = $conn->select()
            ->from(
                ['s' => $salesTable],
                [
                    'date' => 's.aggregate_date',
                    'revenue_total' => 'COALESCE(SUM(s.revenue_total), 0)',
                    'orders_count' => 'COALESCE(SUM(s.orders_count), 0)',
                    'customer_growth' => 'COALESCE(SUM(s.new_customers_count), 0)',
                ]
            )
            ->where('s.aggregate_date >= ?', (string)$filters['from_date'])
            ->where('s.aggregate_date <= ?', (string)$filters['to_date'])
            ->group('s.aggregate_date')
            ->order('s.aggregate_date ASC');

        $customerSummary = $conn->select()
            ->from(
                ['c' => $customerTable],
                [
                    'active_customers_90d' => 'COALESCE(SUM(CASE WHEN c.days_since_last_order IS NOT NULL AND c.days_since_last_order <= 90 THEN 1 ELSE 0 END), 0)',
                    'avg_order_value' => 'COALESCE(AVG(c.avg_order_value_90d), 0)',
                    'returning_customers' => 'COALESCE(SUM(CASE WHEN c.orders_count_90d > 1 THEN 1 ELSE 0 END), 0)',
                    'new_customers' => 'COALESCE(SUM(CASE WHEN c.orders_count_90d = 1 THEN 1 ELSE 0 END), 0)',
                    'risk_low' => 'COALESCE(SUM(CASE WHEN c.risk_level = "low" THEN 1 ELSE 0 END), 0)',
                    'risk_medium' => 'COALESCE(SUM(CASE WHEN c.risk_level = "medium" THEN 1 ELSE 0 END), 0)',
                    'risk_high' => 'COALESCE(SUM(CASE WHEN c.risk_level = "high" THEN 1 ELSE 0 END), 0)',
                ]
            );

        $customerSummary->where(
            'DATE(COALESCE(c.last_order_at, c.updated_at)) >= ?',
            $fromDate
        )->where(
            'DATE(COALESCE(c.last_order_at, c.updated_at)) <= ?',
            $toDate
        );

        $segmentCounts = $conn->select()
            ->from(
                ['cs' => $segmentTable],
                [
                    'segment_code' => 'cs.segment_code',
                    'segment_count' => 'COUNT(*)',
                ]
            )
            ->group('cs.segment_code');

        $notificationsSummary = $conn->select()
            ->from(
                ['n' => $notificationTable],
                [
                    'unread_notifications' => 'COALESCE(SUM(CASE WHEN n.status = "new" THEN 1 ELSE 0 END), 0)',
                ]
            )
            ->where('DATE(n.created_at) >= ?', $fromDate)
            ->where('DATE(n.created_at) <= ?', $toDate);

        $notificationsFeed = $conn->select()
            ->from(
                ['n' => $notificationTable],
                [
                    'notification_id' => 'n.notification_id',
                    'severity' => 'n.severity',
                    'title' => 'n.title',
                    'status' => 'n.status',
                    'created_at' => 'n.created_at',
                ]
            )
            ->where('n.status = ?', 'new')
            ->where('DATE(n.created_at) >= ?', $fromDate)
            ->where('DATE(n.created_at) <= ?', $toDate)
            ->order('n.created_at DESC');

        if (($filters['store_id'] ?? null) !== null && (int)$filters['store_id'] > 0) {
            $storeId = (int)$filters['store_id'];
            $salesSummary->where('s.store_id = ?', $storeId);
            $salesTrend->where('s.store_id = ?', $storeId);
            $customerSummary->where('c.store_id = ?', $storeId);
            $segmentCounts->where('cs.store_id = ?', $storeId);
        }

        $salesRow = (array)$conn->fetchRow($salesSummary);
        $customerRow = (array)$conn->fetchRow($customerSummary);
        $trendRows = $conn->fetchAll($salesTrend);
        $segmentRows = $conn->fetchAll($segmentCounts);
        $notificationRow = (array)$conn->fetchRow($notificationsSummary);
        $notificationFeedRows = $conn->fetchAll($notificationsFeed);

        $labels = [];
        $revenueValues = [];
        $orderValues = [];
        $customerGrowthValues = [];
        foreach ($trendRows as $row) {
            $labels[] = (string)$row['date'];
            $revenueValues[] = (float)$row['revenue_total'];
            $orderValues[] = (int)$row['orders_count'];
            $customerGrowthValues[] = (int)$row['customer_growth'];
        }

        $segmentDefaults = [
            'NEW_CUSTOMER' => 0,
            'FREQUENT_BUYER' => 0,
            'HIGH_VALUE_CUSTOMER' => 0,
            'DISCOUNT_HUNTER' => 0,
            'NEW_ARRIVAL_BUYER' => 0,
            'CATEGORY_LOYAL' => 0,
            'AT_RISK_CUSTOMER' => 0,
            'INACTIVE_CUSTOMER' => 0,
        ];
        foreach ($segmentRows as $row) {
            $code = strtoupper((string)($row['segment_code'] ?? ''));
            if (isset($segmentDefaults[$code])) {
                $segmentDefaults[$code] = (int)($row['segment_count'] ?? 0);
            }
        }

        $returningCustomers = (int)($customerRow['returning_customers'] ?? 0);
        $newCustomers = (int)($customerRow['new_customers'] ?? 0);
        $baseCustomerCount = $returningCustomers + $newCustomers;
        $returningPct = $baseCustomerCount > 0 ? round(($returningCustomers / $baseCustomerCount) * 100, 2) : 0.0;

        return [
            'summary' => [
                'section' => 'overview',
                'range' => $range,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'grid' => is_array($notificationFeedRows) ? $notificationFeedRows : [],
            'charts' => [
                'revenue_trend' => [
                    'labels' => $labels,
                    'values' => $revenueValues,
                ],
                'order_trend' => [
                    'labels' => $labels,
                    'values' => $orderValues,
                ],
                'customer_growth' => [
                    'labels' => $labels,
                    'values' => $customerGrowthValues,
                ],
                'risk_distribution' => [
                    'labels' => ['Low', 'Medium', 'High'],
                    'values' => [
                        (int)($customerRow['risk_low'] ?? 0),
                        (int)($customerRow['risk_medium'] ?? 0),
                        (int)($customerRow['risk_high'] ?? 0),
                    ],
                ],
            ],
            'widgets' => [
                'business_health' => [
                    'range_label' => strtoupper($range === 'custom' ? 'CUSTOM' : $range),
                    'total_revenue' => (float)($salesRow['total_revenue'] ?? 0),
                    'orders_count' => (int)($salesRow['orders_count'] ?? 0),
                    'active_customers' => (int)($customerRow['active_customers_90d'] ?? 0),
                    'avg_order_value' => round((float)($customerRow['avg_order_value'] ?? 0), 2),
                    'returning_customer_percent' => $returningPct,
                ],
                'segment_counts' => $segmentDefaults,
                'risk_monitor' => [
                    'low' => (int)($customerRow['risk_low'] ?? 0),
                    'medium' => (int)($customerRow['risk_medium'] ?? 0),
                    'high' => (int)($customerRow['risk_high'] ?? 0),
                ],
                'acquisition_retention' => [
                    'new_customers' => $newCustomers,
                    'returning_customers' => $returningCustomers,
                ],
                'alerts' => [
                    'unread_notifications' => (int)($notificationRow['unread_notifications'] ?? 0),
                ],
                'notifications_feed' => is_array($notificationFeedRows) ? $notificationFeedRows : [],
            ],
        ];
    }
}