<?php
namespace ArtM\AdminAI\Model\ResourceModel\Dashboard;

use Magento\Framework\App\ResourceConnection;

class ProductData
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
        $table = $this->resource->getTableName('artm_adminai_product_analytics_day');

        $summarySelect = $conn->select()
            ->from(
                ['p' => $table],
                [
                    'top_selling_products' => 'COUNT(DISTINCT p.product_id)',
                    'low_stock_projection' => 'COALESCE(SUM(CASE WHEN p.days_of_stock IS NOT NULL AND p.days_of_stock <= 14 THEN 1 ELSE 0 END), 0)',
                    'slow_moving_products' => 'COALESCE(SUM(CASE WHEN p.qty_sold <= 1 AND p.stock_qty > 0 THEN 1 ELSE 0 END), 0)',
                ]
            )
            ->where('p.aggregate_date >= ?', (string)$filters['from_date'])
            ->where('p.aggregate_date <= ?', (string)$filters['to_date']);

        $gridSelect = $conn->select()
            ->from(
                ['p' => $table],
                [
                    'product_id' => 'p.product_id',
                    'sku' => 'p.sku',
                    'qty_sold' => 'COALESCE(SUM(p.qty_sold), 0)',
                    'revenue_total' => 'COALESCE(SUM(p.revenue_total), 0)',
                    'stock_qty' => 'COALESCE(MAX(p.stock_qty), 0)',
                    'days_of_stock' => 'COALESCE(MAX(p.days_of_stock), 0)',
                ]
            )
            ->where('p.aggregate_date >= ?', (string)$filters['from_date'])
            ->where('p.aggregate_date <= ?', (string)$filters['to_date'])
            ->group(['p.product_id', 'p.sku'])
            ->order('revenue_total DESC');

        $stockProjectionRangeDays = (int)($filters['stock_projection_days'] ?? 90);
        if (!in_array($stockProjectionRangeDays, [90, 180, 360], true)) {
            $stockProjectionRangeDays = 90;
        }
        $stockProjectionDaysThreshold = 15;
        $stockProjectionQtyThreshold = 3;

        $latestAggregateDateSelect = $conn->select()
            ->from(['pd' => $table], ['max_date' => 'MAX(pd.aggregate_date)']);

        if (($filters['store_id'] ?? null) !== null && (int)$filters['store_id'] > 0) {
            $summarySelect->where('p.store_id = ?', (int)$filters['store_id']);
            $gridSelect->where('p.store_id = ?', (int)$filters['store_id']);
            $latestAggregateDateSelect->where('pd.store_id = ?', (int)$filters['store_id']);
        }

        $stockProjectionToDate = (string)$conn->fetchOne($latestAggregateDateSelect);
        if ($stockProjectionToDate === '') {
            $stockProjectionToDate = (string)$filters['to_date'];
        }
        $stockProjectionFromDate = date('Y-m-d', strtotime($stockProjectionToDate . ' -' . max(1, $stockProjectionRangeDays - 1) . ' days'));

        $salesWindowSelect = $conn->select()
            ->from(
                ['p' => $table],
                [
                    'product_id' => 'p.product_id',
                    'sku' => 'p.sku',
                    'store_id' => 'p.store_id',
                    'qty_sold_window' => 'COALESCE(SUM(p.qty_sold), 0)',
                ]
            )
            ->where('p.aggregate_date >= ?', $stockProjectionFromDate)
            ->where('p.aggregate_date <= ?', $stockProjectionToDate)
            ->group(['p.product_id', 'p.sku', 'p.store_id'])
            ->having('COALESCE(SUM(p.qty_sold), 0) > 0');

        $latestStockSubSelect = $conn->select()
            ->from(
                ['ps' => $table],
                [
                    'product_id' => 'ps.product_id',
                    'store_id' => 'ps.store_id',
                    'max_aggregate_date' => 'MAX(ps.aggregate_date)',
                ]
            )
            ->group(['ps.product_id', 'ps.store_id']);

        if (($filters['store_id'] ?? null) !== null && (int)$filters['store_id'] > 0) {
            $salesWindowSelect->where('p.store_id = ?', (int)$filters['store_id']);
            $latestStockSubSelect->where('ps.store_id = ?', (int)$filters['store_id']);
        }

        $avgDailyExpr = 'CASE WHEN sw.qty_sold_window > 0 THEN (sw.qty_sold_window / ' . (int)$stockProjectionRangeDays . ') ELSE 0 END';
        $daysRemainingExpr = 'CASE WHEN sw.qty_sold_window > 0 THEN ROUND(COALESCE(ps.stock_qty, 0) / (' . $avgDailyExpr . '), 2) ELSE 0 END';

        $stockProjectionSelect = $conn->select()
            ->from(
                ['sw' => $salesWindowSelect],
                [
                    'product_id' => 'sw.product_id',
                    'product' => 'sw.sku',
                    'stock_left' => 'COALESCE(ps.stock_qty, 0)',
                    'qty_sold_in_window' => 'ROUND(sw.qty_sold_window, 2)',
                    'avg_daily_sale' => new \Zend_Db_Expr('ROUND(' . $avgDailyExpr . ', 4)'),
                    'days_remaining' => new \Zend_Db_Expr($daysRemainingExpr),
                ]
            )
            ->joinInner(
                ['latest' => $latestStockSubSelect],
                'latest.product_id = sw.product_id AND latest.store_id = sw.store_id',
                []
            )
            ->joinInner(
                ['ps' => $table],
                'ps.product_id = latest.product_id AND ps.store_id = latest.store_id AND ps.aggregate_date = latest.max_aggregate_date',
                []
            )
            ->order('sw.sku ASC');

        $summary = (array)$conn->fetchRow($summarySelect);
        $grid = $conn->fetchAll($gridSelect);
        $stockProjectionRows = $conn->fetchAll($stockProjectionSelect);

        $storeId = ($filters['store_id'] ?? null) !== null ? (int)$filters['store_id'] : null;
        $widgetProductIds = [];

        if (is_array($grid)) {
            foreach ($grid as $gridRow) {
                $pid = (int)($gridRow['product_id'] ?? 0);
                if ($pid > 0) {
                    $widgetProductIds[] = $pid;
                }
            }
        }

        if (is_array($stockProjectionRows)) {
            foreach ($stockProjectionRows as $projectionRow) {
                $pid = (int)($projectionRow['product_id'] ?? 0);
                if ($pid > 0) {
                    $widgetProductIds[] = $pid;
                }
            }
        }

        $widgetProductNameMap = $this->getCatalogProductNamesByIds($conn, $widgetProductIds, $storeId);

        $periodFrom = (string)$filters['from_date'];
        $periodTo = (string)$filters['to_date'];
        $fromTs = strtotime($periodFrom . ' 00:00:00');
        $toTs = strtotime($periodTo . ' 00:00:00');
        $days = ($fromTs !== false && $toTs !== false && $toTs >= $fromTs)
            ? (int)floor(($toTs - $fromTs) / 86400) + 1
            : 30;
        $prevTo = date('Y-m-d', strtotime($periodFrom . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . max(1, $days - 1) . ' days'));

        $prevMapSelect = $conn->select()
            ->from(
                ['p' => $table],
                [
                    'sku' => 'p.sku',
                    'prev_qty_sold' => 'COALESCE(SUM(p.qty_sold), 0)',
                ]
            )
            ->where('p.aggregate_date >= ?', $prevFrom)
            ->where('p.aggregate_date <= ?', $prevTo)
            ->group('p.sku');

        if (($filters['store_id'] ?? null) !== null && (int)$filters['store_id'] > 0) {
            $prevMapSelect->where('p.store_id = ?', (int)$filters['store_id']);
        }

        $prevRows = $conn->fetchAll($prevMapSelect);
        $prevSkuQtyMap = [];
        foreach ($prevRows as $prevRow) {
            $prevSkuQtyMap[(string)$prevRow['sku']] = (float)$prevRow['prev_qty_sold'];
        }

        $labels = [];
        $values = [];
        $trendingProducts = [];
        $stockProjection = [];
        $slowMoving = [];
        $trendingLimit = 100;

        if (is_array($grid)) {
            foreach ($grid as $index => $row) {
                if ($index < 10) {
                    $labels[] = (string)$row['sku'];
                    $values[] = (float)$row['revenue_total'];
                }

                $currentQty = (float)$row['qty_sold'];
                $prevQty = (float)($prevSkuQtyMap[(string)$row['sku']] ?? 0.0);
                $growthPct = $prevQty > 0.0001 ? round((($currentQty - $prevQty) / $prevQty) * 100, 2) : ($currentQty > 0 ? 100.0 : 0.0);

                if (count($trendingProducts) < $trendingLimit) {
                    $productLabel = $this->buildNameSkuLabel(
                        (int)($row['product_id'] ?? 0),
                        (string)$row['sku'],
                        $widgetProductNameMap
                    );

                    $trendingProducts[] = [
                        'product' => $productLabel,
                        'qty_sold' => round($currentQty, 2),
                        'revenue' => round((float)$row['revenue_total'], 2),
                        'growth_pct' => $growthPct,
                    ];
                }

                if ((float)$row['stock_qty'] > 0 && $currentQty <= 1.0) {
                    $productLabel = $this->buildNameSkuLabel(
                        (int)($row['product_id'] ?? 0),
                        (string)$row['sku'],
                        $widgetProductNameMap
                    );

                    $slowMoving[] = [
                        'product' => $productLabel,
                        'stock_qty' => round((float)$row['stock_qty'], 2),
                        'qty_sold' => round($currentQty, 2),
                        'revenue' => round((float)$row['revenue_total'], 2),
                    ];
                }
            }
        }

        if (is_array($stockProjectionRows)) {
            foreach ($stockProjectionRows as $stockRow) {
                $daysRemaining = (float)($stockRow['days_remaining'] ?? 0);
                $stockLeft = (float)($stockRow['stock_left'] ?? 0);
                $isLowStockDays = $daysRemaining > 0 && $daysRemaining < $stockProjectionDaysThreshold;
                $isLowStockQty = $stockLeft < $stockProjectionQtyThreshold;
                if (!$isLowStockDays && !$isLowStockQty) {
                    continue;
                }

                $status = 'OK';
                if ($stockLeft <= 0.0001 && $daysRemaining <= 0.0001) {
                    $status = 'O-O-S';
                } elseif ($isLowStockDays || $isLowStockQty) {
                    $status = 'ORDER_SOON';
                } elseif ($daysRemaining <= 0.0001 && $stockLeft > 0.0001) {
                    $status = 'REVIEW';
                }

                $sku = (string)($stockRow['product'] ?? '');
                $productId = (int)($stockRow['product_id'] ?? 0);
                $productLabel = $this->buildNameSkuLabel($productId, $sku, $widgetProductNameMap);

                $stockProjection[] = [
                    'product' => $productLabel,
                    'stock_left' => round($stockLeft, 2),
                    'qty_sold_in_window' => round((float)($stockRow['qty_sold_in_window'] ?? 0), 2),
                    'avg_daily_sale' => round((float)($stockRow['avg_daily_sale'] ?? 0), 4),
                    'days_remaining' => round($daysRemaining, 2),
                    'status' => $status,
                ];
            }
        }

        $skuTrackerSku = strtoupper(trim((string)($filters['sku_tracker_sku'] ?? '')));
        $skuTrackerFrom = (string)($filters['sku_tracker_from_date'] ?? '');
        $skuTrackerTo = (string)($filters['sku_tracker_to_date'] ?? '');
        if ($skuTrackerFrom === '' || $skuTrackerTo === '') {
            $skuTrackerFrom = '';
            $skuTrackerTo = '';
        }
        $skuTracker = $this->buildSkuTracker($conn, $filters, $skuTrackerSku, $skuTrackerFrom, $skuTrackerTo);

        return [
            'summary' => [
                'section' => 'products',
                'from_date' => (string)$filters['from_date'],
                'to_date' => (string)$filters['to_date'],
                'top_selling_products' => (int)($summary['top_selling_products'] ?? 0),
                'low_stock_projection' => (int)($summary['low_stock_projection'] ?? 0),
                'slow_moving_products' => (int)($summary['slow_moving_products'] ?? 0),
            ],
            'grid' => is_array($grid) ? $grid : [],
            'charts' => [
                'product_performance' => [
                    'labels' => $labels,
                    'values' => $values,
                ],
            ],
            'widgets' => [
                'trending_products' => $trendingProducts,
                'stock_projection' => $stockProjection,
                'stock_projection_range_days' => $stockProjectionRangeDays,
                'slow_moving_products' => $slowMoving,
                'sku_tracker' => $skuTracker,
            ],
        ];
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function buildSkuTracker($conn, array $filters, string $sku, string $fromDate, string $toDate): array
    {
        $empty = [
            'found' => false,
            'query_sku' => $sku,
            'message' => $sku === '' ? 'Enter SKU to track product performance.' : 'SKU not found.',
            'period' => [
                'mode' => ($fromDate !== '' && $toDate !== '') ? 'range' : 'all_time',
                'from_date' => $fromDate ?: null,
                'to_date' => $toDate ?: null,
            ],
            'product' => null,
            'children' => [],
            'kpis' => [
                'total_revenue' => 0,
                'total_qty_sold' => 0,
                'total_orders' => 0,
                'lowest_price' => 0,
                'highest_price' => 0,
            ],
            'chart' => [
                'labels' => [],
                'values' => [],
            ],
            'orders' => [],
        ];

        if ($sku === '') {
            return $empty;
        }

        $productTable = $this->resource->getTableName('catalog_product_entity');
        $superLinkTable = $this->resource->getTableName('catalog_product_super_link');
        $orderTable = $this->resource->getTableName('sales_order');
        $orderItemTable = $this->resource->getTableName('sales_order_item');

        $productRow = $conn->fetchRow(
            $conn->select()
                ->from(['p' => $productTable], ['entity_id', 'sku', 'type_id'])
                ->where('p.sku = ?', $sku)
                ->limit(1)
        );

        if (!is_array($productRow) || empty($productRow)) {
            return $empty;
        }

        $storeId = ($filters['store_id'] ?? null) !== null ? (int)$filters['store_id'] : null;
        $entityId = (int)$productRow['entity_id'];
        $typeId = (string)$productRow['type_id'];
        $normalizedSku = (string)$productRow['sku'];

        $product = $this->fetchProductSnapshot($conn, $entityId, $normalizedSku, $typeId, $storeId);

        $children = [];
        $trackedSkus = [$normalizedSku];
        if ($typeId === 'configurable') {
            $childRows = $conn->fetchAll(
                $conn->select()
                    ->from(['sl' => $superLinkTable], [])
                    ->joinInner(['c' => $productTable], 'c.entity_id = sl.product_id', ['entity_id', 'sku', 'type_id'])
                    ->where('sl.parent_id = ?', $entityId)
                    ->order('c.sku ASC')
            );

            $trackedSkus = [];
            foreach ($childRows as $childRow) {
                $childSku = (string)$childRow['sku'];
                $trackedSkus[] = $childSku;
                $children[] = $this->fetchProductSnapshot(
                    $conn,
                    (int)$childRow['entity_id'],
                    $childSku,
                    (string)$childRow['type_id'],
                    $storeId
                );
            }

            if (empty($trackedSkus)) {
                $trackedSkus = [$normalizedSku];
            }
        }

        $priceExpr = 'COALESCE(oi.base_price_incl_tax, oi.base_price, oi.price_incl_tax, oi.price, 0)';
        $summarySelect = $conn->select()
            ->from(
                ['oi' => $orderItemTable],
                [
                    'total_revenue' => 'COALESCE(SUM(oi.base_row_total_incl_tax), 0)',
                    'total_qty_sold' => 'COALESCE(SUM(oi.qty_ordered), 0)',
                    'total_orders' => 'COUNT(DISTINCT o.entity_id)',
                    'lowest_price' => 'COALESCE(MIN(' . $priceExpr . '), 0)',
                    'highest_price' => 'COALESCE(MAX(' . $priceExpr . '), 0)',
                ]
            )
            ->joinInner(['o' => $orderTable], 'o.entity_id = oi.order_id', [])
            ->where('oi.sku IN (?)', $trackedSkus)
            ->where('o.state <> ?', 'canceled');

        $ordersSelect = $conn->select()
            ->from(
                ['oi' => $orderItemTable],
                [
                    'order_id' => 'o.increment_id',
                    'qty_sold' => 'oi.qty_ordered',
                    'date' => 'DATE(o.created_at)',
                    'price' => new \Zend_Db_Expr($priceExpr),
                    'revenue' => 'oi.base_row_total_incl_tax',
                ]
            )
            ->joinInner(['o' => $orderTable], 'o.entity_id = oi.order_id', [])
            ->where('oi.sku IN (?)', $trackedSkus)
            ->where('o.state <> ?', 'canceled')
            ->order('o.created_at DESC')
            ->limit(500);

        $chartDateExpr = ($fromDate !== '' && $toDate !== '')
            ? 'DATE(o.created_at)'
            : 'DATE_FORMAT(o.created_at, "%Y-%m")';
        $chartSelect = $conn->select()
            ->from(
                ['oi' => $orderItemTable],
                [
                    'bucket' => new \Zend_Db_Expr($chartDateExpr),
                    'revenue_total' => 'COALESCE(SUM(oi.base_row_total_incl_tax), 0)',
                ]
            )
            ->joinInner(['o' => $orderTable], 'o.entity_id = oi.order_id', [])
            ->where('oi.sku IN (?)', $trackedSkus)
            ->where('o.state <> ?', 'canceled')
            ->group(new \Zend_Db_Expr($chartDateExpr))
            ->order('bucket ASC');

        if ($storeId !== null && $storeId > 0) {
            $summarySelect->where('o.store_id = ?', $storeId);
            $ordersSelect->where('o.store_id = ?', $storeId);
            $chartSelect->where('o.store_id = ?', $storeId);
        }

        if ($fromDate !== '' && $toDate !== '') {
            $summarySelect->where('DATE(o.created_at) >= ?', $fromDate)
                ->where('DATE(o.created_at) <= ?', $toDate);
            $ordersSelect->where('DATE(o.created_at) >= ?', $fromDate)
                ->where('DATE(o.created_at) <= ?', $toDate);
            $chartSelect->where('DATE(o.created_at) >= ?', $fromDate)
                ->where('DATE(o.created_at) <= ?', $toDate);
        }

        $summary = (array)$conn->fetchRow($summarySelect);
        $orders = $conn->fetchAll($ordersSelect);
        $chartRows = $conn->fetchAll($chartSelect);

        $chartLabels = [];
        $chartValues = [];
        foreach ($chartRows as $chartRow) {
            $chartLabels[] = (string)($chartRow['bucket'] ?? '');
            $chartValues[] = round((float)($chartRow['revenue_total'] ?? 0), 2);
        }

        $orderRows = [];
        foreach ($orders as $orderRow) {
            $orderRows[] = [
                'order_id' => (string)($orderRow['order_id'] ?? ''),
                'qty_sold' => round((float)($orderRow['qty_sold'] ?? 0), 2),
                'date' => (string)($orderRow['date'] ?? ''),
                'price' => round((float)($orderRow['price'] ?? 0), 2),
                'revenue' => round((float)($orderRow['revenue'] ?? 0), 2),
            ];
        }

        return [
            'found' => true,
            'query_sku' => $normalizedSku,
            'message' => '',
            'period' => [
                'mode' => ($fromDate !== '' && $toDate !== '') ? 'range' : 'all_time',
                'from_date' => $fromDate ?: null,
                'to_date' => $toDate ?: null,
            ],
            'product' => $product,
            'children' => $children,
            'kpis' => [
                'total_revenue' => round((float)($summary['total_revenue'] ?? 0), 2),
                'total_qty_sold' => round((float)($summary['total_qty_sold'] ?? 0), 2),
                'total_orders' => (int)($summary['total_orders'] ?? 0),
                'lowest_price' => round((float)($summary['lowest_price'] ?? 0), 2),
                'highest_price' => round((float)($summary['highest_price'] ?? 0), 2),
            ],
            'chart' => [
                'labels' => $chartLabels,
                'values' => $chartValues,
            ],
            'orders' => $orderRows,
        ];
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @return array<string,mixed>
     */
    private function fetchProductSnapshot($conn, int $entityId, string $sku, string $typeId, ?int $storeId): array
    {
        $varcharTable = $this->resource->getTableName('catalog_product_entity_varchar');
        $decimalTable = $this->resource->getTableName('catalog_product_entity_decimal');
        $inventoryTable = $this->resource->getTableName('inventory_source_item');

        $nameAttrId = $this->getCatalogAttributeId($conn, 'name');
        $imageAttrId = $this->getCatalogAttributeId($conn, 'image');
        $priceAttrId = $this->getCatalogAttributeId($conn, 'price');

        $effectiveStoreId = ($storeId !== null && $storeId > 0) ? $storeId : 0;

        $name = $sku;
        if ($nameAttrId > 0) {
            $name = (string)$conn->fetchOne(
                $conn->select()
                    ->from(['v' => $varcharTable], [new \Zend_Db_Expr('COALESCE(MAX(CASE WHEN v.store_id = ' . (int)$effectiveStoreId . ' THEN v.value END), MAX(CASE WHEN v.store_id = 0 THEN v.value END))')])
                    ->where('v.entity_id = ?', $entityId)
                    ->where('v.attribute_id = ?', $nameAttrId)
            );
            if ($name === '') {
                $name = $sku;
            }
        }

        $image = '';
        if ($imageAttrId > 0) {
            $image = (string)$conn->fetchOne(
                $conn->select()
                    ->from(['v' => $varcharTable], [new \Zend_Db_Expr('COALESCE(MAX(CASE WHEN v.store_id = ' . (int)$effectiveStoreId . ' THEN v.value END), MAX(CASE WHEN v.store_id = 0 THEN v.value END))')])
                    ->where('v.entity_id = ?', $entityId)
                    ->where('v.attribute_id = ?', $imageAttrId)
            );
        }

        $price = 0.0;
        if ($priceAttrId > 0) {
            $price = (float)$conn->fetchOne(
                $conn->select()
                    ->from(['d' => $decimalTable], [new \Zend_Db_Expr('COALESCE(MAX(CASE WHEN d.store_id = ' . (int)$effectiveStoreId . ' THEN d.value END), MAX(CASE WHEN d.store_id = 0 THEN d.value END), 0)')])
                    ->where('d.entity_id = ?', $entityId)
                    ->where('d.attribute_id = ?', $priceAttrId)
            );
        }

        $stockQty = (float)$conn->fetchOne(
            $conn->select()
                ->from(['i' => $inventoryTable], ['qty' => 'COALESCE(SUM(i.quantity), 0)'])
                ->where('i.sku = ?', $sku)
        );

        $imageUrl = '';
        $image = trim((string)$image);
        if ($image !== '' && $image !== 'no_selection') {
            $imageUrl = '/media/catalog/product' . (strpos($image, '/') === 0 ? $image : '/' . $image);
        }

        return [
            'id' => $entityId,
            'sku' => $sku,
            'name' => $name,
            'type' => $typeId,
            'price' => round($price, 2),
            'stock_qty' => round($stockQty, 2),
            'is_out_of_stock' => $stockQty <= 0.0001,
            'image_url' => $imageUrl,
        ];
    }

    private function getCatalogAttributeId($conn, string $attributeCode): int
    {
        $entityTypeTable = $this->resource->getTableName('eav_entity_type');
        $attributeTable = $this->resource->getTableName('eav_attribute');

        $entityTypeId = (int)$conn->fetchOne(
            $conn->select()
                ->from(['et' => $entityTypeTable], ['entity_type_id'])
                ->where('et.entity_type_code = ?', 'catalog_product')
                ->limit(1)
        );

        if ($entityTypeId <= 0) {
            return 0;
        }

        return (int)$conn->fetchOne(
            $conn->select()
                ->from(['a' => $attributeTable], ['attribute_id'])
                ->where('a.entity_type_id = ?', $entityTypeId)
                ->where('a.attribute_code = ?', $attributeCode)
                ->limit(1)
        );
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param int[] $entityIds
     * @return array<int,string>
     */
    private function getCatalogProductNamesByIds($conn, array $entityIds, ?int $storeId): array
    {
        $entityIds = array_values(array_unique(array_filter(array_map('intval', $entityIds), function ($id) {
            return $id > 0;
        })));

        if (empty($entityIds)) {
            return [];
        }

        $nameAttrId = $this->getCatalogAttributeId($conn, 'name');
        if ($nameAttrId <= 0) {
            return [];
        }

        $varcharTable = $this->resource->getTableName('catalog_product_entity_varchar');
        $effectiveStoreId = ($storeId !== null && $storeId > 0) ? $storeId : 0;

        $rows = $conn->fetchAll(
            $conn->select()
                ->from(
                    ['v' => $varcharTable],
                    [
                        'entity_id' => 'v.entity_id',
                        'name' => new \Zend_Db_Expr(
                            'COALESCE('
                            . 'MAX(CASE WHEN v.store_id = ' . (int)$effectiveStoreId . ' THEN v.value END), '
                            . 'MAX(CASE WHEN v.store_id = 0 THEN v.value END)'
                            . ')'
                        ),
                    ]
                )
                ->where('v.attribute_id = ?', $nameAttrId)
                ->where('v.entity_id IN (?)', $entityIds)
                ->group('v.entity_id')
        );

        $nameMap = [];
        foreach ($rows as $row) {
            $id = (int)($row['entity_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $name = trim((string)($row['name'] ?? ''));
            if ($name !== '') {
                $nameMap[$id] = $name;
            }
        }

        return $nameMap;
    }

    /**
     * @param array<int,string> $nameMap
     */
    private function buildNameSkuLabel(int $productId, string $sku, array $nameMap): string
    {
        $sku = trim($sku);
        $name = trim((string)($nameMap[$productId] ?? ''));

        if ($name === '' || strcasecmp($name, $sku) === 0) {
            return $sku;
        }

        return $name . ' (' . $sku . ')';
    }
}