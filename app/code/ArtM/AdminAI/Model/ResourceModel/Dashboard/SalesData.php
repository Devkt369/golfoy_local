<?php
namespace ArtM\AdminAI\Model\ResourceModel\Dashboard;

use Magento\Framework\App\ResourceConnection;

class SalesData
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
        $table = $this->resource->getTableName('artm_adminai_sales_aggregate_day');
        $productTable = $this->resource->getTableName('artm_adminai_product_analytics_day');
        $productVarcharTable = $this->resource->getTableName('catalog_product_entity_varchar');
        $eavAttributeTable = $this->resource->getTableName('eav_attribute');
        $eavEntityTypeTable = $this->resource->getTableName('eav_entity_type');

        $entityTypeId = (int)$conn->fetchOne(
            $conn->select()
                ->from($eavEntityTypeTable, ['entity_type_id'])
                ->where('entity_type_code = ?', 'catalog_product')
                ->limit(1)
        );

        $nameAttributeId = 0;
        if ($entityTypeId > 0) {
            $nameAttributeId = (int)$conn->fetchOne(
                $conn->select()
                    ->from($eavAttributeTable, ['attribute_id'])
                    ->where('entity_type_id = ?', $entityTypeId)
                    ->where('attribute_code = ?', 'name')
                    ->limit(1)
            );
        }

        $fromDate = (string)$filters['from_date'];
        $toDate = (string)$filters['to_date'];

        $summarySelect = $conn->select()
            ->from(
                ['s' => $table],
                [
                    'orders_count' => 'COALESCE(SUM(s.orders_count), 0)',
                    'revenue_total' => 'COALESCE(SUM(s.revenue_total), 0)',
                    'items_qty_total' => 'COALESCE(SUM(s.items_qty_total), 0)',
                    'new_customers_count' => 'COALESCE(SUM(s.new_customers_count), 0)',
                ]
            )
            ->where('s.aggregate_date >= ?', $fromDate)
            ->where('s.aggregate_date <= ?', $toDate);

        $gridSelect = $conn->select()
            ->from(
                ['s' => $table],
                [
                    'date' => 's.aggregate_date',
                    'orders_count' => 's.orders_count',
                    'revenue_total' => 's.revenue_total',
                    'items_qty_total' => 's.items_qty_total',
                    'new_customers_count' => 's.new_customers_count',
                ]
            )
            ->where('s.aggregate_date >= ?', $fromDate)
            ->where('s.aggregate_date <= ?', $toDate)
            ->order('s.aggregate_date DESC');

        $chartSelect = $conn->select()
            ->from(
                ['s' => $table],
                [
                    'date' => 's.aggregate_date',
                    'revenue_total' => 'COALESCE(SUM(s.revenue_total), 0)',
                    'orders_count' => 'COALESCE(SUM(s.orders_count), 0)',
                    'items_qty_total' => 'COALESCE(SUM(s.items_qty_total), 0)',
                    'customer_growth' => 'COALESCE(SUM(s.new_customers_count), 0)',
                ]
            )
            ->where('s.aggregate_date >= ?', $fromDate)
            ->where('s.aggregate_date <= ?', $toDate)
            ->group('s.aggregate_date')
            ->order('s.aggregate_date ASC');

        $fromTs = strtotime($fromDate . ' 00:00:00');
        $toTs = strtotime($toDate . ' 00:00:00');
        $days = ($fromTs !== false && $toTs !== false && $toTs >= $fromTs)
            ? (int)floor(($toTs - $fromTs) / 86400) + 1
            : 30;
        $prevTo = date('Y-m-d', strtotime($fromDate . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . max(1, $days - 1) . ' days'));

        $prevSummarySelect = $conn->select()
            ->from(
                ['s' => $table],
                [
                    'orders_count' => 'COALESCE(SUM(s.orders_count), 0)',
                    'revenue_total' => 'COALESCE(SUM(s.revenue_total), 0)',
                    'new_customers_count' => 'COALESCE(SUM(s.new_customers_count), 0)',
                ]
            )
            ->where('s.aggregate_date >= ?', $prevFrom)
            ->where('s.aggregate_date <= ?', $prevTo);

        $categoryLikeSelect = $conn->select()
            ->from(
                ['p' => $productTable],
                [
                    'bucket' => 'p.sku',
                    'revenue_total' => 'COALESCE(SUM(p.revenue_total), 0)',
                    'units_sold' => 'COALESCE(SUM(p.qty_sold), 0)',
                ]
            )
            ->where('p.aggregate_date >= ?', $fromDate)
            ->where('p.aggregate_date <= ?', $toDate)
            ->group('p.sku')
            ->order('revenue_total DESC')
            ->limit(8);

        $topProductsAggSelect = $conn->select()
            ->from(
                ['p' => $productTable],
                [
                    'product_id' => 'p.product_id',
                    'sku' => 'p.sku',
                    'qty_sold' => 'COALESCE(SUM(p.qty_sold), 0)',
                    'revenue_total' => 'COALESCE(SUM(p.revenue_total), 0)',
                ]
            )
            ->where('p.aggregate_date >= ?', $fromDate)
            ->where('p.aggregate_date <= ?', $toDate)
            ->group(['p.product_id', 'p.sku'])
            ->order('qty_sold DESC')
            ->limit(50);

        $topProductsSelect = $conn->select()
            ->from(
                ['tp' => $topProductsAggSelect],
                [
                    'product_id' => 'tp.product_id',
                    'sku' => 'tp.sku',
                    'qty_sold' => new \Zend_Db_Expr('ROUND(tp.qty_sold, 2)'),
                    'revenue_total' => new \Zend_Db_Expr('ROUND(tp.revenue_total, 2)'),
                    'avg_selling_price' => new \Zend_Db_Expr('CASE WHEN tp.qty_sold > 0 THEN ROUND(tp.revenue_total / tp.qty_sold, 2) ELSE 0 END'),
                ]
            )
            ->order('tp.qty_sold DESC');

        $storeId = null;
        if (($filters['store_id'] ?? null) !== null && (int)$filters['store_id'] > 0) {
            $storeId = (int)$filters['store_id'];
            $summarySelect->where('s.store_id = ?', $storeId);
            $gridSelect->where('s.store_id = ?', $storeId);
            $chartSelect->where('s.store_id = ?', $storeId);
            $prevSummarySelect->where('s.store_id = ?', $storeId);
            $categoryLikeSelect->where('p.store_id = ?', $storeId);
            $topProductsAggSelect->where('p.store_id = ?', $storeId);
        }

        if ($nameAttributeId > 0) {
            $topProductsSelect->joinLeft(
                ['n0' => $productVarcharTable],
                'n0.entity_id = tp.product_id AND n0.attribute_id = ' . (int)$nameAttributeId . ' AND n0.store_id = 0',
                []
            );

            if ($storeId !== null && $storeId > 0) {
                $topProductsSelect->joinLeft(
                    ['n1' => $productVarcharTable],
                    'n1.entity_id = tp.product_id AND n1.attribute_id = ' . (int)$nameAttributeId . ' AND n1.store_id = ' . (int)$storeId,
                    ['product_name' => new \Zend_Db_Expr('COALESCE(n1.value, n0.value, "-")')]
                );
            } else {
                $topProductsSelect->columns(['product_name' => new \Zend_Db_Expr('COALESCE(n0.value, "-")')]);
            }
        } else {
            $topProductsSelect->columns(['product_name' => new \Zend_Db_Expr('"-"')]);
        }

        $summary = (array)$conn->fetchRow($summarySelect);
        $grid = $conn->fetchAll($gridSelect);
        $chartRows = $conn->fetchAll($chartSelect);
        $prevSummary = (array)$conn->fetchRow($prevSummarySelect);
        $categoryLikeRows = $conn->fetchAll($categoryLikeSelect);
        $topProductsRows = $conn->fetchAll($topProductsSelect);

        $labels = [];
        $revenueValues = [];
        $orderValues = [];
        $itemValues = [];
        $customerGrowthValues = [];
        foreach ($chartRows as $row) {
            $labels[] = (string)$row['date'];
            $revenueValues[] = (float)$row['revenue_total'];
            $orderValues[] = (int)$row['orders_count'];
            $itemValues[] = (float)$row['items_qty_total'];
            $customerGrowthValues[] = (int)$row['customer_growth'];
        }

        $catLabels = [];
        $catRevenue = [];
        $catUnits = [];
        foreach ($categoryLikeRows as $row) {
            $catLabels[] = (string)$row['bucket'];
            $catRevenue[] = round((float)$row['revenue_total'], 2);
            $catUnits[] = round((float)$row['units_sold'], 2);
        }

        $curRevenue = (float)($summary['revenue_total'] ?? 0);
        $prevRevenue = (float)($prevSummary['revenue_total'] ?? 0);
        $revenueGrowthPct = $prevRevenue > 0.0001
            ? round((($curRevenue - $prevRevenue) / $prevRevenue) * 100, 2)
            : ($curRevenue > 0 ? 100.0 : 0.0);

        $topBucket = '';
        $topBucketRevenue = 0.0;
        if (!empty($categoryLikeRows)) {
            $topBucket = (string)$categoryLikeRows[0]['bucket'];
            $topBucketRevenue = (float)$categoryLikeRows[0]['revenue_total'];
        }

        $executiveSummary = [
            'Sales changed ' . $revenueGrowthPct . '% versus previous comparable period.',
            $topBucket !== ''
                ? ('Top driver: ' . $topBucket . ' contributed ' . round($topBucketRevenue, 2) . ' revenue.')
                : 'No product-bucket contribution data available for this range.',
            'Customer growth events in range: ' . array_sum($customerGrowthValues) . '.',
        ];

        return [
            'summary' => [
                'section' => 'sales',
                'from_date' => (string)$filters['from_date'],
                'to_date' => (string)$filters['to_date'],
                'orders_count' => (int)($summary['orders_count'] ?? 0),
                'revenue_total' => (float)($summary['revenue_total'] ?? 0),
                'items_qty_total' => (float)($summary['items_qty_total'] ?? 0),
                'new_customers_count' => (int)($summary['new_customers_count'] ?? 0),
            ],
            'grid' => is_array($grid) ? $grid : [],
            'charts' => [
                'revenue_trend' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Revenue',
                            'data' => $revenueValues,
                        ],
                    ],
                    'meta' => [
                        'orders' => $orderValues,
                        'items' => $itemValues,
                    ],
                    'values' => $revenueValues,
                ],
                'order_trend' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Orders',
                            'data' => $orderValues,
                        ],
                    ],
                    'meta' => [
                        'revenue' => $revenueValues,
                        'items' => $itemValues,
                    ],
                    'values' => $orderValues,
                ],
                'customer_growth' => [
                    'labels' => $labels,
                    'values' => $customerGrowthValues,
                ],
                'category_performance' => [
                    'labels' => $catLabels,
                    'revenue_values' => $catRevenue,
                    'units_values' => $catUnits,
                ],
            ],
            'widgets' => [
                'executive_sales_summary' => $executiveSummary,
                'top_sold_products' => is_array($topProductsRows) ? $topProductsRows : [],
            ],
        ];
    }
}
