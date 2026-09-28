<?php
namespace ArtM\AdminAI\Model\ResourceModel\Dashboard;

use Magento\Framework\App\ResourceConnection;

class CustomerData
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
        $customerTable = $this->resource->getTableName('customer_entity');
        $orderTable = $this->resource->getTableName('sales_order');
        $orderItemTable = $this->resource->getTableName('sales_order_item');
        $addressTable = $this->resource->getTableName('customer_address_entity');

        $storeId = isset($filters['store_id']) && (int)$filters['store_id'] > 0 ? (int)$filters['store_id'] : null;
        $period = $this->resolveCustomerPeriod((string)($filters['customer_period'] ?? '90d'));
        $lostThresholdDays = $this->resolveLostThresholdDays($period);

        $customerRows = $this->fetchCustomerRows(
            $conn,
            $customerTable,
            $orderTable,
            $period['from_date'],
            $storeId
        );

        $activeRows = [];
        $inactiveRows = [];
        $superActiveCount = 0;
        $superDuperCount = 0;
        foreach ($customerRows as $row) {
            $periodOrders = (int)($row['period_orders'] ?? 0);
            $prepared = [
                'customer_id' => (int)($row['customer_id'] ?? 0),
                'name' => (string)($row['name'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'total_revenue' => round((float)($row['total_revenue'] ?? 0), 2),
                'total_orders' => (int)($row['total_orders'] ?? 0),
                'avg_order_value' => round((float)($row['avg_order_value'] ?? 0), 2),
                'customer_since' => (string)($row['customer_since'] ?? ''),
                'order_dates' => (string)($row['order_dates'] ?? ''),
            ];

            if ($periodOrders > 0) {
                $activeRows[] = $prepared;
                if ($periodOrders <= 5) {
                    $superActiveCount++;
                } else {
                    $superDuperCount++;
                }
                continue;
            }

            $inactiveRows[] = $prepared;
        }

        $categorySignals = $this->fetchCustomerCategorySignals(
            $conn,
            $orderTable,
            $orderItemTable,
            $period['from_date'],
            $storeId
        );
        $newArrivalSignals = $this->fetchCustomerNewArrivalSignals(
            $conn,
            $orderTable,
            $orderItemTable,
            $period['from_date'],
            $storeId
        );
        $segmentWidgets = $this->buildCustomerSegmentWidgets(
            $customerRows,
            $categorySignals,
            $newArrivalSignals,
            $lostThresholdDays,
            $period['from_date']
        );

        $recentPurchaseMix = $this->fetchRecentPurchaseMix(
            $conn,
            $orderTable,
            $orderItemTable,
            $period['from_date'],
            $storeId
        );

        $zeroPriceRows = $this->fetchZeroPricePurchaseRows(
            $conn,
            $customerTable,
            $orderTable,
            $orderItemTable,
            $period['from_date'],
            $storeId
        );

        $lookup = $this->buildCustomerLookup(
            $conn,
            $customerTable,
            $addressTable,
            $orderTable,
            $orderItemTable,
            (string)($filters['customer_lookup_query'] ?? ''),
            $storeId
        );

        return [
            'summary' => [
                'section' => 'customers',
                'period_code' => $period['code'],
                'period_label' => $period['label'],
                'period_from_date' => $period['from_date'],
                'active_customers' => count($activeRows),
                'inactive_customers' => count($inactiveRows),
                'new_customers' => count($segmentWidgets['customer_new_customers']),
                'super_active_customers' => $superActiveCount,
                'super_duper_customers' => $superDuperCount,
                'frequent_buyers' => count($segmentWidgets['customer_frequent_buyers']),
                'high_value_customers' => count($segmentWidgets['customer_high_value_customers']),
                'discount_hunters' => count($segmentWidgets['customer_discount_hunters']),
                'at_risk_customers' => count($segmentWidgets['customer_at_risk_customers']),
                'lost_customers' => count($segmentWidgets['customer_lost_customers']),
            ],
            'grid' => [],
            'charts' => [],
            'widgets' => array_merge(
                [
                    'customer_period' => [
                        'code' => $period['code'],
                        'label' => $period['label'],
                        'from_date' => $period['from_date'],
                    ],
                    'customer_active' => $activeRows,
                    'customer_inactive' => $inactiveRows,
                    'customer_recent_purchases' => $recentPurchaseMix,
                    'customer_lookup' => $lookup,
                    'customer_zero_price_purchases' => $zeroPriceRows,
                ],
                $segmentWidgets
            ),
        ];
    }

    /**
     * @return array{code:string,label:string,from_date:string,days:int}
     */
    private function resolveCustomerPeriod(string $code): array
    {
        $normalized = strtolower(trim($code));
        $map = [
            '90d' => ['label' => 'Last 90 Days', 'modifier' => '-89 days', 'days' => 90],
            '180d' => ['label' => 'Last 180 Days', 'modifier' => '-179 days', 'days' => 180],
            '360d' => ['label' => 'Last 360 Days', 'modifier' => '-359 days', 'days' => 360],
            '2y' => ['label' => 'Last 2 Years', 'modifier' => '-2 years', 'days' => 730],
            '3y' => ['label' => 'Last 3 Years', 'modifier' => '-3 years', 'days' => 1095],
            '4y' => ['label' => 'Last 4 Years', 'modifier' => '-4 years', 'days' => 1460],
            '5y' => ['label' => 'Last 5 Years', 'modifier' => '-5 years', 'days' => 1825],
        ];

        if (!isset($map[$normalized])) {
            $normalized = '90d';
        }

        $timestamp = strtotime($map[$normalized]['modifier']);
        if ($timestamp === false) {
            $timestamp = strtotime('-89 days') ?: time();
        }

        return [
            'code' => $normalized,
            'label' => $map[$normalized]['label'],
            'from_date' => gmdate('Y-m-d', $timestamp),
            'days' => (int)$map[$normalized]['days'],
        ];
    }

    /**
     * @param array{days:int} $period
     */
    private function resolveLostThresholdDays(array $period): int
    {
        $days = isset($period['days']) ? (int)$period['days'] : 90;
        return max(180, min(365, $days));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchCustomerRows($conn, string $customerTable, string $orderTable, string $periodFromDate, ?int $storeId): array
    {
        $periodOrders = $conn->select()
            ->from(
                ['o' => $orderTable],
                [
                    'customer_id' => 'o.customer_id',
                    'period_orders' => new \Zend_Db_Expr('COUNT(*)'),
                    'period_revenue' => new \Zend_Db_Expr('SUM(o.base_grand_total)'),
                    'period_discount_orders' => new \Zend_Db_Expr('SUM(CASE WHEN ABS(o.discount_amount) > 0.0001 THEN 1 ELSE 0 END)'),
                    'period_discount_amount' => new \Zend_Db_Expr('SUM(ABS(o.discount_amount))'),
                    'period_last_order_at' => new \Zend_Db_Expr('MAX(o.created_at)')
                ]
            )
            ->where('o.customer_id IS NOT NULL')
            ->where('o.state NOT IN (?)', ['canceled', 'closed'])
            ->where('DATE(o.created_at) >= ?', $periodFromDate)
            ->group('o.customer_id');

        if ($storeId !== null) {
            $periodOrders->where('o.store_id = ?', $storeId);
        }

        $allOrders = $conn->select()
            ->from(
                ['o' => $orderTable],
                [
                    'customer_id' => 'o.customer_id',
                    'total_revenue' => new \Zend_Db_Expr('SUM(o.base_grand_total)'),
                    'total_orders' => new \Zend_Db_Expr('COUNT(*)'),
                    'avg_order_value' => new \Zend_Db_Expr('AVG(o.base_grand_total)'),
                    'first_order_at' => new \Zend_Db_Expr('MIN(o.created_at)'),
                    'last_order_at' => new \Zend_Db_Expr('MAX(o.created_at)'),
                    'order_dates' => new \Zend_Db_Expr("GROUP_CONCAT(CONCAT(o.increment_id, ' | ', DATE_FORMAT(o.created_at, '%Y-%m-%d')) ORDER BY o.created_at DESC SEPARATOR '\n')")
                ]
            )
            ->where('o.customer_id IS NOT NULL')
            ->where('o.state NOT IN (?)', ['canceled', 'closed'])
            ->group('o.customer_id');

        if ($storeId !== null) {
            $allOrders->where('o.store_id = ?', $storeId);
        }

        $lastOrders = $conn->select()
            ->from(
                ['o' => $orderTable],
                [
                    'customer_id' => 'o.customer_id',
                    'last_order_at' => new \Zend_Db_Expr('MAX(o.created_at)')
                ]
            )
            ->where('o.customer_id IS NOT NULL')
            ->where('o.state NOT IN (?)', ['canceled', 'closed'])
            ->group('o.customer_id');

        if ($storeId !== null) {
            $lastOrders->where('o.store_id = ?', $storeId);
        }

        $previousOrders = $conn->select()
            ->from(
                ['o' => $orderTable],
                [
                    'customer_id' => 'o.customer_id',
                    'previous_order_at' => new \Zend_Db_Expr('MAX(o.created_at)')
                ]
            )
            ->joinInner(['lo' => new \Zend_Db_Expr('(' . $lastOrders . ')')], 'lo.customer_id = o.customer_id', [])
            ->where('o.customer_id IS NOT NULL')
            ->where('o.state NOT IN (?)', ['canceled', 'closed'])
            ->where('o.created_at < lo.last_order_at')
            ->group('o.customer_id');

        if ($storeId !== null) {
            $previousOrders->where('o.store_id = ?', $storeId);
        }

        $select = $conn->select()
            ->from(
                ['ce' => $customerTable],
                [
                    'customer_id' => 'ce.entity_id',
                    'name' => new \Zend_Db_Expr("TRIM(CONCAT(COALESCE(ce.firstname, ''), ' ', COALESCE(ce.lastname, '')))"),
                    'email' => 'ce.email',
                    'customer_since' => new \Zend_Db_Expr('DATE(ce.created_at)'),
                    'total_revenue' => new \Zend_Db_Expr('COALESCE(ao.total_revenue, 0)'),
                    'total_orders' => new \Zend_Db_Expr('COALESCE(ao.total_orders, 0)'),
                    'avg_order_value' => new \Zend_Db_Expr('COALESCE(ao.avg_order_value, 0)'),
                    'first_order_at' => 'ao.first_order_at',
                    'last_order_at' => 'ao.last_order_at',
                    'previous_order_at' => 'pr.previous_order_at',
                    'order_dates' => new \Zend_Db_Expr('COALESCE(ao.order_dates, "")'),
                    'period_orders' => new \Zend_Db_Expr('COALESCE(po.period_orders, 0)'),
                    'period_revenue' => new \Zend_Db_Expr('COALESCE(po.period_revenue, 0)'),
                    'period_discount_orders' => new \Zend_Db_Expr('COALESCE(po.period_discount_orders, 0)'),
                    'period_discount_amount' => new \Zend_Db_Expr('COALESCE(po.period_discount_amount, 0)'),
                    'period_last_order_at' => 'po.period_last_order_at',
                    'days_since_last_order' => new \Zend_Db_Expr('CASE WHEN ao.last_order_at IS NULL THEN NULL ELSE DATEDIFF(UTC_DATE(), DATE(ao.last_order_at)) END')
                ]
            )
            ->joinLeft(['po' => new \Zend_Db_Expr('(' . $periodOrders . ')')], 'po.customer_id = ce.entity_id', [])
            ->joinLeft(['ao' => new \Zend_Db_Expr('(' . $allOrders . ')')], 'ao.customer_id = ce.entity_id', [])
            ->joinLeft(['pr' => new \Zend_Db_Expr('(' . $previousOrders . ')')], 'pr.customer_id = ce.entity_id', [])
            ->order(new \Zend_Db_Expr('COALESCE(po.period_orders, 0) DESC'))
            ->order(new \Zend_Db_Expr('COALESCE(po.period_last_order_at, ao.last_order_at, ce.created_at) DESC'));

        return array_values(array_filter((array)$conn->fetchAll($select), 'is_array'));
    }

    /**
     * @return array<int,string>
     */
    private function fetchRecentPurchaseMix($conn, string $orderTable, string $orderItemTable, string $periodFromDate, ?int $storeId): array
    {
        $select = $conn->select()
            ->from(
                ['soi' => $orderItemTable],
                [
                    'sku' => 'soi.sku',
                    'name' => new \Zend_Db_Expr('MAX(soi.name)'),
                    'qty_ordered' => new \Zend_Db_Expr('SUM(soi.qty_ordered)'),
                    'orders_count' => new \Zend_Db_Expr('COUNT(DISTINCT so.entity_id)')
                ]
            )
            ->joinInner(['so' => $orderTable], 'so.entity_id = soi.order_id', [])
            ->where('so.state NOT IN (?)', ['canceled', 'closed'])
            ->where('DATE(so.created_at) >= ?', $periodFromDate)
            ->where('soi.parent_item_id IS NULL')
            ->group('soi.sku')
            ->order('qty_ordered DESC')
            ->limit(8);

        if ($storeId !== null) {
            $select->where('so.store_id = ?', $storeId);
        }

        $rows = $conn->fetchAll($select);
        $items = [];
        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            $sku = (string)($row['sku'] ?? '');
            $items[] = $sku
                . ($name !== '' ? ' - ' . $name : '')
                . ' - Qty ' . number_format((float)($row['qty_ordered'] ?? 0), 2)
                . ' in ' . (int)($row['orders_count'] ?? 0) . ' orders';
        }

        return $items;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildCustomerLookup(
        $conn,
        string $customerTable,
        string $addressTable,
        string $orderTable,
        string $orderItemTable,
        string $rawQuery,
        ?int $storeId
    ): array {
        $query = trim($rawQuery);
        if ($query === '') {
            return [
                'query' => '',
                'found' => false,
                'message' => 'Search by customer ID or email to view profile, addresses, and orders.',
                'customer' => null,
                'addresses' => [],
                'recent_buys' => [],
                'orders' => [],
            ];
        }

        $customerSelect = $conn->select()
            ->from(['ce' => $customerTable])
            ->limit(1);

        if (ctype_digit($query)) {
            $customerSelect->where('ce.entity_id = ?', (int)$query);
        } else {
            $customerSelect->where('ce.email = ?', $query);
            $customerSelect->orWhere('ce.email LIKE ?', $query . '%');
        }

        $customer = $conn->fetchRow($customerSelect);
        if (!is_array($customer) || empty($customer['entity_id'])) {
            return [
                'query' => $query,
                'found' => false,
                'message' => 'No customer found for the provided search.',
                'customer' => null,
                'addresses' => [],
                'recent_buys' => [],
                'orders' => [],
            ];
        }

        $customerId = (int)$customer['entity_id'];

        $orderSummarySelect = $conn->select()
            ->from(
                ['o' => $orderTable],
                [
                    'orders_count' => new \Zend_Db_Expr('COUNT(*)'),
                    'total_spent' => new \Zend_Db_Expr('SUM(o.base_grand_total)'),
                    'last_order_at' => new \Zend_Db_Expr('MAX(o.created_at)')
                ]
            )
            ->where('o.customer_id = ?', $customerId)
            ->where('o.state NOT IN (?)', ['canceled', 'closed']);

        if ($storeId !== null) {
            $orderSummarySelect->where('o.store_id = ?', $storeId);
        }

        $orderSummary = (array)$conn->fetchRow($orderSummarySelect);

        $addressRows = $conn->fetchAll(
            $conn->select()
                ->from(
                    ['ca' => $addressTable],
                    [
                        'address_id' => 'ca.entity_id',
                        'firstname' => 'ca.firstname',
                        'lastname' => 'ca.lastname',
                        'company' => 'ca.company',
                        'street' => 'ca.street',
                        'city' => 'ca.city',
                        'region' => 'ca.region',
                        'postcode' => 'ca.postcode',
                        'country_id' => 'ca.country_id',
                        'telephone' => 'ca.telephone'
                    ]
                )
                ->where('ca.parent_id = ?', $customerId)
                ->order('ca.entity_id ASC')
        );

        $defaultBilling = isset($customer['default_billing']) ? (int)$customer['default_billing'] : 0;
        $defaultShipping = isset($customer['default_shipping']) ? (int)$customer['default_shipping'] : 0;
        $addresses = [];
        foreach ($addressRows as $address) {
            $addressId = (int)($address['address_id'] ?? 0);
            $types = [];
            if ($addressId === $defaultBilling) {
                $types[] = 'Billing';
            }
            if ($addressId === $defaultShipping) {
                $types[] = 'Shipping';
            }
            if (!$types) {
                $types[] = 'Address';
            }

            $addresses[] = [
                'type' => implode(' / ', $types),
                'name' => trim((string)($address['firstname'] ?? '') . ' ' . (string)($address['lastname'] ?? '')),
                'company' => (string)($address['company'] ?? ''),
                'street' => (string)($address['street'] ?? ''),
                'city' => (string)($address['city'] ?? ''),
                'region' => (string)($address['region'] ?? ''),
                'postcode' => (string)($address['postcode'] ?? ''),
                'country' => (string)($address['country_id'] ?? ''),
                'telephone' => (string)($address['telephone'] ?? ''),
            ];
        }

        $recentBuySelect = $conn->select()
            ->from(
                ['soi' => $orderItemTable],
                [
                    'sku' => 'soi.sku',
                    'name' => new \Zend_Db_Expr('MAX(soi.name)'),
                    'qty_ordered' => new \Zend_Db_Expr('SUM(soi.qty_ordered)'),
                    'orders_count' => new \Zend_Db_Expr('COUNT(DISTINCT so.entity_id)')
                ]
            )
            ->joinInner(['so' => $orderTable], 'so.entity_id = soi.order_id', [])
            ->where('so.customer_id = ?', $customerId)
            ->where('so.state NOT IN (?)', ['canceled', 'closed'])
            ->where('so.created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 90 DAY)')
            ->where('soi.parent_item_id IS NULL')
            ->group('soi.sku')
            ->order('qty_ordered DESC')
            ->limit(8);

        if ($storeId !== null) {
            $recentBuySelect->where('so.store_id = ?', $storeId);
        }

        $recentBuyRows = $conn->fetchAll($recentBuySelect);
        $recentBuys = [];
        foreach ($recentBuyRows as $row) {
            $recentBuys[] = [
                'sku' => (string)($row['sku'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
                'qty_ordered' => round((float)($row['qty_ordered'] ?? 0), 2),
                'orders_count' => (int)($row['orders_count'] ?? 0),
            ];
        }

        $orderSelect = $conn->select()
            ->from(
                ['o' => $orderTable],
                [
                    'order_id' => 'o.increment_id',
                    'order_date' => 'o.created_at',
                    'status' => 'o.status',
                    'grand_total' => 'o.base_grand_total',
                    'store_id' => 'o.store_id',
                    'entity_id' => 'o.entity_id'
                ]
            )
            ->where('o.customer_id = ?', $customerId)
            ->order('o.created_at DESC');

        if ($storeId !== null) {
            $orderSelect->where('o.store_id = ?', $storeId);
        }

        $orderRows = $conn->fetchAll($orderSelect);
        $orderEntityIds = [];
        foreach ($orderRows as $orderRow) {
            $orderEntityIds[] = (int)($orderRow['entity_id'] ?? 0);
        }

        $orderItemMap = [];
        if ($orderEntityIds) {
            $itemRows = $conn->fetchAll(
                $conn->select()
                    ->from(
                        ['soi' => $orderItemTable],
                        [
                            'order_id' => 'soi.order_id',
                            'skus' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT soi.sku ORDER BY soi.sku SEPARATOR ", ")'),
                            'qty_items' => new \Zend_Db_Expr('SUM(soi.qty_ordered)')
                        ]
                    )
                    ->where('soi.order_id IN (?)', $orderEntityIds)
                    ->where('soi.parent_item_id IS NULL')
                    ->group('soi.order_id')
            );

            foreach ($itemRows as $itemRow) {
                $orderItemMap[(int)($itemRow['order_id'] ?? 0)] = [
                    'skus' => (string)($itemRow['skus'] ?? ''),
                    'qty_items' => round((float)($itemRow['qty_items'] ?? 0), 2),
                ];
            }
        }

        $orders = [];
        foreach ($orderRows as $orderRow) {
            $entityId = (int)($orderRow['entity_id'] ?? 0);
            $itemMeta = $orderItemMap[$entityId] ?? ['skus' => '', 'qty_items' => 0];
            $orders[] = [
                'order_id' => (string)($orderRow['order_id'] ?? ''),
                'order_date' => (string)($orderRow['order_date'] ?? ''),
                'status' => (string)($orderRow['status'] ?? ''),
                'grand_total' => round((float)($orderRow['grand_total'] ?? 0), 2),
                'qty_items' => $itemMeta['qty_items'],
                'skus' => $itemMeta['skus'],
            ];
        }

        return [
            'query' => $query,
            'found' => true,
            'message' => '',
            'customer' => [
                'customer_id' => $customerId,
                'name' => trim((string)($customer['firstname'] ?? '') . ' ' . (string)($customer['lastname'] ?? '')),
                'email' => (string)($customer['email'] ?? ''),
                'group_id' => (int)($customer['group_id'] ?? 0),
                'customer_since' => isset($customer['created_at']) ? substr((string)$customer['created_at'], 0, 10) : '',
                'dob' => (string)($customer['dob'] ?? ''),
                'gender' => (string)($customer['gender'] ?? ''),
                'orders_count' => (int)($orderSummary['orders_count'] ?? 0),
                'total_spent' => round((float)($orderSummary['total_spent'] ?? 0), 2),
                'last_order_at' => (string)($orderSummary['last_order_at'] ?? ''),
            ],
            'addresses' => $addresses,
            'recent_buys' => $recentBuys,
            'orders' => $orders,
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function fetchCustomerCategorySignals($conn, string $orderTable, string $orderItemTable, string $periodFromDate, ?int $storeId): array
    {
        $categoryLinkTable = $this->resource->getTableName('catalog_category_product');

        $primaryCategorySelect = $conn->select()
            ->from(
                ['ccp' => $categoryLinkTable],
                [
                    'product_id' => 'ccp.product_id',
                    'category_id' => new \Zend_Db_Expr('MIN(ccp.category_id)')
                ]
            )
            ->group('ccp.product_id');

        $select = $conn->select()
            ->from(
                ['soi' => $orderItemTable],
                [
                    'customer_id' => 'so.customer_id',
                    'category_id' => 'pc.category_id',
                    'qty_ordered' => new \Zend_Db_Expr('SUM(soi.qty_ordered)')
                ]
            )
            ->joinInner(['so' => $orderTable], 'so.entity_id = soi.order_id', [])
            ->joinLeft(['pc' => new \Zend_Db_Expr('(' . $primaryCategorySelect . ')')], 'pc.product_id = soi.product_id', [])
            ->where('so.customer_id IS NOT NULL')
            ->where('so.state NOT IN (?)', ['canceled', 'closed'])
            ->where('DATE(so.created_at) >= ?', $periodFromDate)
            ->where('soi.parent_item_id IS NULL')
            ->where('pc.category_id IS NOT NULL')
            ->group(['so.customer_id', 'pc.category_id']);

        if ($storeId !== null) {
            $select->where('so.store_id = ?', $storeId);
        }

        $rows = $conn->fetchAll($select);
        $byCustomer = [];
        $categoryIds = [];
        foreach ($rows as $row) {
            $customerId = (int)($row['customer_id'] ?? 0);
            $categoryId = (int)($row['category_id'] ?? 0);
            $qty = (float)($row['qty_ordered'] ?? 0);
            if ($customerId <= 0 || $categoryId <= 0 || $qty <= 0) {
                continue;
            }

            if (!isset($byCustomer[$customerId])) {
                $byCustomer[$customerId] = [
                    'total_qty' => 0.0,
                    'dominant_qty' => 0.0,
                    'dominant_category_id' => 0,
                ];
            }

            $byCustomer[$customerId]['total_qty'] += $qty;
            if ($qty > (float)$byCustomer[$customerId]['dominant_qty']) {
                $byCustomer[$customerId]['dominant_qty'] = $qty;
                $byCustomer[$customerId]['dominant_category_id'] = $categoryId;
            }
            $categoryIds[$categoryId] = $categoryId;
        }

        if (!$byCustomer) {
            return [];
        }

        $categoryNames = $this->fetchCategoryNamesByIds($conn, array_values($categoryIds));
        $signals = [];
        foreach ($byCustomer as $customerId => $signal) {
            $totalQty = (float)$signal['total_qty'];
            $dominantQty = (float)$signal['dominant_qty'];
            $categoryId = (int)$signal['dominant_category_id'];
            if ($totalQty <= 0 || $categoryId <= 0) {
                continue;
            }

            $signals[$customerId] = [
                'dominant_category' => $categoryNames[$categoryId] ?? ('Category #' . $categoryId),
                'category_share_pct' => round(($dominantQty / $totalQty) * 100, 2),
                'dominant_qty' => round($dominantQty, 2),
                'total_qty' => round($totalQty, 2),
            ];
        }

        return $signals;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function fetchCustomerNewArrivalSignals($conn, string $orderTable, string $orderItemTable, string $periodFromDate, ?int $storeId): array
    {
        $productTable = $this->resource->getTableName('catalog_product_entity');

        $select = $conn->select()
            ->from(
                ['soi' => $orderItemTable],
                [
                    'customer_id' => 'so.customer_id',
                    'new_arrival_qty' => new \Zend_Db_Expr('SUM(CASE WHEN p.created_at IS NOT NULL AND DATEDIFF(DATE(so.created_at), DATE(p.created_at)) BETWEEN 0 AND 60 THEN soi.qty_ordered ELSE 0 END)'),
                    'total_qty' => new \Zend_Db_Expr('SUM(soi.qty_ordered)'),
                    'new_arrival_orders' => new \Zend_Db_Expr('COUNT(DISTINCT CASE WHEN p.created_at IS NOT NULL AND DATEDIFF(DATE(so.created_at), DATE(p.created_at)) BETWEEN 0 AND 60 THEN so.entity_id ELSE NULL END)'),
                    'last_new_arrival_order' => new \Zend_Db_Expr('MAX(CASE WHEN p.created_at IS NOT NULL AND DATEDIFF(DATE(so.created_at), DATE(p.created_at)) BETWEEN 0 AND 60 THEN so.created_at ELSE NULL END)')
                ]
            )
            ->joinInner(['so' => $orderTable], 'so.entity_id = soi.order_id', [])
            ->joinLeft(['p' => $productTable], 'p.entity_id = soi.product_id', [])
            ->where('so.customer_id IS NOT NULL')
            ->where('so.state NOT IN (?)', ['canceled', 'closed'])
            ->where('DATE(so.created_at) >= ?', $periodFromDate)
            ->where('soi.parent_item_id IS NULL')
            ->group('so.customer_id');

        if ($storeId !== null) {
            $select->where('so.store_id = ?', $storeId);
        }

        $rows = $conn->fetchAll($select);
        $signals = [];
        foreach ($rows as $row) {
            $customerId = (int)($row['customer_id'] ?? 0);
            $totalQty = (float)($row['total_qty'] ?? 0);
            $newArrivalQty = (float)($row['new_arrival_qty'] ?? 0);
            if ($customerId <= 0 || $totalQty <= 0 || $newArrivalQty <= 0) {
                continue;
            }

            $signals[$customerId] = [
                'new_arrival_orders' => (int)($row['new_arrival_orders'] ?? 0),
                'new_arrival_qty' => round($newArrivalQty, 2),
                'new_arrival_ratio_pct' => round(($newArrivalQty / $totalQty) * 100, 2),
                'last_new_arrival_order' => (string)($row['last_new_arrival_order'] ?? ''),
            ];
        }

        return $signals;
    }

    /**
     * @param array<int,array<string,mixed>> $customerRows
     * @param array<string,array<string,mixed>> $categorySignals
     * @param array<string,array<string,mixed>> $newArrivalSignals
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function buildCustomerSegmentWidgets(
        array $customerRows,
        array $categorySignals,
        array $newArrivalSignals,
        int $lostThresholdDays,
        string $periodFromDate
    ): array {
        $widgets = [
            'customer_new_customers' => [],
            'customer_frequent_buyers' => [],
            'customer_high_value_customers' => [],
            'customer_discount_hunters' => [],
            'customer_category_loyal_customers' => [],
            'customer_new_arrival_buyers' => [],
            'customer_at_risk_customers' => [],
            'customer_reactivated_customers' => [],
            'customer_one_time_buyers' => [],
            'customer_lost_customers' => [],
        ];

        $positivePeriodRevenue = [];
        foreach ($customerRows as $row) {
            $periodRevenue = (float)($row['period_revenue'] ?? 0);
            if ($periodRevenue > 0) {
                $positivePeriodRevenue[] = $periodRevenue;
            }
        }
        $avgPositivePeriodRevenue = $positivePeriodRevenue
            ? array_sum($positivePeriodRevenue) / count($positivePeriodRevenue)
            : 0.0;

        foreach ($customerRows as $row) {
            $customerId = (int)($row['customer_id'] ?? 0);
            if ($customerId <= 0) {
                continue;
            }

            $name = (string)($row['name'] ?? '');
            $email = (string)($row['email'] ?? '');
            $customerSince = (string)($row['customer_since'] ?? '');
            $firstOrderAt = (string)($row['first_order_at'] ?? '');
            $lastOrderAt = (string)($row['last_order_at'] ?? '');
            $previousOrderAt = (string)($row['previous_order_at'] ?? '');
            $totalOrders = (int)($row['total_orders'] ?? 0);
            $periodOrders = (int)($row['period_orders'] ?? 0);
            $periodRevenue = round((float)($row['period_revenue'] ?? 0), 2);
            $lifetimeRevenue = round((float)($row['total_revenue'] ?? 0), 2);
            $avgOrderValue = round((float)($row['avg_order_value'] ?? 0), 2);
            $periodDiscountOrders = (int)($row['period_discount_orders'] ?? 0);
            $periodDiscountAmount = round((float)($row['period_discount_amount'] ?? 0), 2);
            $daysSinceLastOrder = isset($row['days_since_last_order']) && $row['days_since_last_order'] !== null
                ? (int)$row['days_since_last_order']
                : null;

            $avgPurchaseGapDays = null;
            if ($totalOrders > 1 && $firstOrderAt !== '' && $lastOrderAt !== '') {
                $firstTs = strtotime($firstOrderAt);
                $lastTs = strtotime($lastOrderAt);
                if ($firstTs !== false && $lastTs !== false && $lastTs >= $firstTs) {
                    $avgPurchaseGapDays = round((($lastTs - $firstTs) / 86400) / max(1, ($totalOrders - 1)), 2);
                }
            }

            if (($customerSince !== '' && $customerSince >= $periodFromDate) || ($firstOrderAt !== '' && substr($firstOrderAt, 0, 10) >= $periodFromDate)) {
                $widgets['customer_new_customers'][] = [
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'customer_since' => $customerSince,
                    'first_order_at' => $firstOrderAt,
                    'total_orders' => $totalOrders,
                    'total_spent' => $lifetimeRevenue,
                    'last_order_at' => $lastOrderAt,
                ];
            }

            if ($periodOrders >= 5) {
                $widgets['customer_frequent_buyers'][] = [
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'orders_in_window' => $periodOrders,
                    'revenue_in_window' => $periodRevenue,
                    'avg_order_value' => $avgOrderValue,
                    'last_order_at' => $lastOrderAt,
                    'customer_since' => $customerSince,
                ];
            }

            if ($avgPositivePeriodRevenue > 0 && $periodRevenue >= ($avgPositivePeriodRevenue * 2)) {
                $widgets['customer_high_value_customers'][] = [
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'revenue_in_window' => $periodRevenue,
                    'orders_in_window' => $periodOrders,
                    'avg_order_value' => $avgOrderValue,
                    'lifetime_revenue' => $lifetimeRevenue,
                    'last_order_at' => $lastOrderAt,
                ];
            }

            if ($periodOrders > 0) {
                $discountRatioPct = round(($periodDiscountOrders / $periodOrders) * 100, 2);
                if ($discountRatioPct >= 60) {
                    $widgets['customer_discount_hunters'][] = [
                        'customer_id' => $customerId,
                        'name' => $name,
                        'email' => $email,
                        'orders_in_window' => $periodOrders,
                        'discounted_orders' => $periodDiscountOrders,
                        'discount_ratio_pct' => $discountRatioPct,
                        'discount_saved' => $periodDiscountAmount,
                        'revenue_in_window' => $periodRevenue,
                        'last_order_at' => $lastOrderAt,
                    ];
                }
            }

            if (isset($categorySignals[$customerId]) && (float)$categorySignals[$customerId]['category_share_pct'] >= 70) {
                $widgets['customer_category_loyal_customers'][] = [
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'dominant_category' => (string)$categorySignals[$customerId]['dominant_category'],
                    'category_share_pct' => (float)$categorySignals[$customerId]['category_share_pct'],
                    'dominant_qty' => (float)$categorySignals[$customerId]['dominant_qty'],
                    'total_qty' => (float)$categorySignals[$customerId]['total_qty'],
                    'last_order_at' => $lastOrderAt,
                ];
            }

            if (isset($newArrivalSignals[$customerId]) && (float)$newArrivalSignals[$customerId]['new_arrival_ratio_pct'] >= 30) {
                $widgets['customer_new_arrival_buyers'][] = [
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'new_arrival_orders' => (int)$newArrivalSignals[$customerId]['new_arrival_orders'],
                    'new_arrival_qty' => (float)$newArrivalSignals[$customerId]['new_arrival_qty'],
                    'new_arrival_ratio_pct' => (float)$newArrivalSignals[$customerId]['new_arrival_ratio_pct'],
                    'last_new_arrival_order' => (string)$newArrivalSignals[$customerId]['last_new_arrival_order'],
                ];
            }

            if ($totalOrders === 1) {
                $widgets['customer_one_time_buyers'][] = [
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'first_order_at' => $firstOrderAt,
                    'order_value' => $lifetimeRevenue,
                    'customer_since' => $customerSince,
                    'days_since_first_order' => $this->calculateDaysSince($firstOrderAt),
                ];
            }

            if ($daysSinceLastOrder !== null && $daysSinceLastOrder >= $lostThresholdDays && $totalOrders > 0) {
                $widgets['customer_lost_customers'][] = [
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'total_orders' => $totalOrders,
                    'lifetime_revenue' => $lifetimeRevenue,
                    'last_order_at' => $lastOrderAt,
                    'days_since_last_order' => $daysSinceLastOrder,
                    'avg_order_value' => $avgOrderValue,
                ];
            }

            if (
                $daysSinceLastOrder !== null
                && $totalOrders > 1
                && $avgPurchaseGapDays !== null
                && $daysSinceLastOrder > max(45, ($avgPurchaseGapDays * 1.5))
                && $daysSinceLastOrder < $lostThresholdDays
            ) {
                $widgets['customer_at_risk_customers'][] = [
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'total_orders' => $totalOrders,
                    'avg_purchase_gap_days' => $avgPurchaseGapDays,
                    'days_since_last_order' => $daysSinceLastOrder,
                    'last_order_at' => $lastOrderAt,
                    'lifetime_revenue' => $lifetimeRevenue,
                ];
            }

            if ($previousOrderAt !== '' && $lastOrderAt !== '' && substr($lastOrderAt, 0, 10) >= $periodFromDate) {
                $previousTs = strtotime($previousOrderAt);
                $lastTs = strtotime($lastOrderAt);
                if ($previousTs !== false && $lastTs !== false && $lastTs > $previousTs) {
                    $inactiveGapDays = (int)floor(($lastTs - $previousTs) / 86400);
                    if ($inactiveGapDays >= 90) {
                        $widgets['customer_reactivated_customers'][] = [
                            'customer_id' => $customerId,
                            'name' => $name,
                            'email' => $email,
                            'last_order_at' => $lastOrderAt,
                            'previous_order_at' => $previousOrderAt,
                            'inactive_gap_days' => $inactiveGapDays,
                            'total_orders' => $totalOrders,
                            'lifetime_revenue' => $lifetimeRevenue,
                        ];
                    }
                }
            }
        }

        $widgets['customer_new_customers'] = $this->sortRowsByKey($widgets['customer_new_customers'], 'customer_since', 'desc');
        $widgets['customer_frequent_buyers'] = $this->sortRowsByKey($widgets['customer_frequent_buyers'], 'orders_in_window', 'desc');
        $widgets['customer_high_value_customers'] = $this->sortRowsByKey($widgets['customer_high_value_customers'], 'revenue_in_window', 'desc');
        $widgets['customer_discount_hunters'] = $this->sortRowsByKey($widgets['customer_discount_hunters'], 'discount_ratio_pct', 'desc');
        $widgets['customer_category_loyal_customers'] = $this->sortRowsByKey($widgets['customer_category_loyal_customers'], 'category_share_pct', 'desc');
        $widgets['customer_new_arrival_buyers'] = $this->sortRowsByKey($widgets['customer_new_arrival_buyers'], 'new_arrival_ratio_pct', 'desc');
        $widgets['customer_at_risk_customers'] = $this->sortRowsByKey($widgets['customer_at_risk_customers'], 'days_since_last_order', 'desc');
        $widgets['customer_reactivated_customers'] = $this->sortRowsByKey($widgets['customer_reactivated_customers'], 'inactive_gap_days', 'desc');
        $widgets['customer_one_time_buyers'] = $this->sortRowsByKey($widgets['customer_one_time_buyers'], 'first_order_at', 'desc');
        $widgets['customer_lost_customers'] = $this->sortRowsByKey($widgets['customer_lost_customers'], 'days_since_last_order', 'desc');

        return $widgets;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchZeroPricePurchaseRows(
        $conn,
        string $customerTable,
        string $orderTable,
        string $orderItemTable,
        string $periodFromDate,
        ?int $storeId
    ): array {
        $productTable = $this->resource->getTableName('catalog_product_entity');

        $select = $conn->select()
            ->from(
                ['soi' => $orderItemTable],
                [
                    'customer_id' => 'so.customer_id',
                    'order_id' => 'so.increment_id',
                    'order_date' => 'so.created_at',
                    'sku' => 'soi.sku',
                    'product_name' => 'soi.name',
                    'product_id' => 'soi.product_id',
                    'qty_ordered' => 'soi.qty_ordered',
                    'item_price' => new \Zend_Db_Expr('COALESCE(soi.base_price, 0)'),
                    'row_total' => new \Zend_Db_Expr('COALESCE(soi.base_row_total, 0)'),
                    'attribute_set_id' => 'p.attribute_set_id',
                    'name' => new \Zend_Db_Expr("TRIM(CONCAT(COALESCE(ce.firstname, ''), ' ', COALESCE(ce.lastname, '')))"),
                    'email' => 'ce.email'
                ]
            )
            ->joinInner(['so' => $orderTable], 'so.entity_id = soi.order_id', [])
            ->joinInner(['ce' => $customerTable], 'ce.entity_id = so.customer_id', [])
            ->joinLeft(['p' => $productTable], 'p.entity_id = soi.product_id', [])
            ->where('so.customer_id IS NOT NULL')
            ->where('so.state NOT IN (?)', ['canceled', 'closed'])
            ->where('DATE(so.created_at) >= ?', $periodFromDate)
            ->where('soi.parent_item_id IS NULL')
            ->where('(ABS(COALESCE(soi.base_price, 0)) < 0.0001 OR ABS(COALESCE(soi.base_row_total, 0)) < 0.0001 OR ABS(COALESCE(soi.base_row_total_incl_tax, 0)) < 0.0001)')
            ->order('so.created_at DESC');

        if ($storeId !== null) {
            $select->where('so.store_id = ?', $storeId);
        }

        $rows = $conn->fetchAll($select);
        if (!$rows) {
            return [];
        }

        $attributeSetIds = [];
        $productIds = [];
        foreach ($rows as $row) {
            $attributeSetId = (int)($row['attribute_set_id'] ?? 0);
            $productId = (int)($row['product_id'] ?? 0);
            if ($attributeSetId > 0) {
                $attributeSetIds[$attributeSetId] = $attributeSetId;
            }
            if ($productId > 0) {
                $productIds[$productId] = $productId;
            }
        }

        $attributeSetNames = $this->fetchAttributeSetNamesByIds($conn, array_values($attributeSetIds));
        $brandLabels = $this->fetchBrandLabelsByProductIds($conn, array_values($productIds));

        $result = [];
        foreach ($rows as $row) {
            $productId = (int)($row['product_id'] ?? 0);
            $attributeSetId = (int)($row['attribute_set_id'] ?? 0);
            $result[] = [
                'customer_id' => (int)($row['customer_id'] ?? 0),
                'name' => (string)($row['name'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'order_id' => (string)($row['order_id'] ?? ''),
                'order_date' => (string)($row['order_date'] ?? ''),
                'sku' => (string)($row['sku'] ?? ''),
                'product_name' => (string)($row['product_name'] ?? ''),
                'brand' => $brandLabels[$productId] ?? '',
                'attribute_set' => $attributeSetNames[$attributeSetId] ?? '',
                'qty_ordered' => round((float)($row['qty_ordered'] ?? 0), 2),
                'item_price' => round((float)($row['item_price'] ?? 0), 2),
                'row_total' => round((float)($row['row_total'] ?? 0), 2),
            ];
        }

        return $result;
    }

    /**
     * @param int[] $attributeSetIds
     * @return array<int,string>
     */
    private function fetchAttributeSetNamesByIds($conn, array $attributeSetIds): array
    {
        $attributeSetIds = array_values(array_unique(array_filter(array_map('intval', $attributeSetIds))));
        if (!$attributeSetIds) {
            return [];
        }

        $table = $this->resource->getTableName('eav_attribute_set');
        $rows = $conn->fetchAll(
            $conn->select()
                ->from(['s' => $table], ['attribute_set_id', 'attribute_set_name'])
                ->where('s.attribute_set_id IN (?)', $attributeSetIds)
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['attribute_set_id']] = (string)$row['attribute_set_name'];
        }

        return $result;
    }

    /**
     * @param int[] $productIds
     * @return array<int,string>
     */
    private function fetchBrandLabelsByProductIds($conn, array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (!$productIds) {
            return [];
        }

        $attribute = $this->getEavAttributeMetadata($conn, 'catalog_product', ['brand', 'manufacturer']);
        if (!$attribute) {
            return [];
        }

        $backendTable = $this->getEntityBackendTable($attribute['entity_type_code'], $attribute['backend_type']);
        if ($backendTable === '') {
            return [];
        }

        $table = $this->resource->getTableName($backendTable);
        $rows = $conn->fetchAll(
            $conn->select()
                ->from(['v' => $table], ['entity_id', 'value'])
                ->where('v.attribute_id = ?', (int)$attribute['attribute_id'])
                ->where('v.entity_id IN (?)', $productIds)
        );

        if (!$rows) {
            return [];
        }

        $result = [];
        if (in_array((string)$attribute['frontend_input'], ['select', 'multiselect'], true) || $attribute['backend_type'] === 'int') {
            $optionIds = [];
            $rawValues = [];
            foreach ($rows as $row) {
                $entityId = (int)($row['entity_id'] ?? 0);
                $value = trim((string)($row['value'] ?? ''));
                if ($entityId <= 0 || $value === '') {
                    continue;
                }

                $rawValues[$entityId] = $value;
                foreach (explode(',', $value) as $part) {
                    $optionId = (int)trim($part);
                    if ($optionId > 0) {
                        $optionIds[$optionId] = $optionId;
                    }
                }
            }

            $optionLabels = $this->fetchOptionLabelsByIds($conn, array_values($optionIds));
            foreach ($rawValues as $entityId => $value) {
                $labels = [];
                foreach (explode(',', $value) as $part) {
                    $optionId = (int)trim($part);
                    if ($optionId > 0 && isset($optionLabels[$optionId])) {
                        $labels[] = $optionLabels[$optionId];
                    }
                }
                $result[$entityId] = implode(', ', array_values(array_unique($labels)));
            }

            return $result;
        }

        foreach ($rows as $row) {
            $entityId = (int)($row['entity_id'] ?? 0);
            if ($entityId <= 0) {
                continue;
            }
            $result[$entityId] = trim((string)($row['value'] ?? ''));
        }

        return $result;
    }

    /**
     * @param int[] $optionIds
     * @return array<int,string>
     */
    private function fetchOptionLabelsByIds($conn, array $optionIds): array
    {
        $optionIds = array_values(array_unique(array_filter(array_map('intval', $optionIds))));
        if (!$optionIds) {
            return [];
        }

        $table = $this->resource->getTableName('eav_attribute_option_value');
        $rows = $conn->fetchAll(
            $conn->select()
                ->from(['ov' => $table], ['option_id', 'value'])
                ->where('ov.option_id IN (?)', $optionIds)
                ->where('ov.store_id = ?', 0)
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['option_id']] = trim((string)($row['value'] ?? ''));
        }

        return $result;
    }

    /**
     * @param int[] $categoryIds
     * @return array<int,string>
     */
    private function fetchCategoryNamesByIds($conn, array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (!$categoryIds) {
            return [];
        }

        $attribute = $this->getEavAttributeMetadata($conn, 'catalog_category', ['name']);
        if (!$attribute) {
            return [];
        }

        $table = $this->resource->getTableName('catalog_category_entity_varchar');
        $rows = $conn->fetchAll(
            $conn->select()
                ->from(['cv' => $table], ['entity_id', 'value'])
                ->where('cv.attribute_id = ?', (int)$attribute['attribute_id'])
                ->where('cv.store_id = ?', 0)
                ->where('cv.entity_id IN (?)', $categoryIds)
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['entity_id']] = trim((string)($row['value'] ?? ''));
        }

        return $result;
    }

    /**
     * @param string[] $attributeCodes
     * @return array<string,mixed>|null
     */
    private function getEavAttributeMetadata($conn, string $entityTypeCode, array $attributeCodes): ?array
    {
        $attributeCodes = array_values(array_filter(array_map('strval', $attributeCodes)));
        if (!$attributeCodes) {
            return null;
        }

        $attributeTable = $this->resource->getTableName('eav_attribute');
        $entityTypeTable = $this->resource->getTableName('eav_entity_type');
        $rows = $conn->fetchAll(
            $conn->select()
                ->from(
                    ['ea' => $attributeTable],
                    ['attribute_id', 'attribute_code', 'backend_type', 'frontend_input']
                )
                ->joinInner(['et' => $entityTypeTable], 'et.entity_type_id = ea.entity_type_id', ['entity_type_code'])
                ->where('et.entity_type_code = ?', $entityTypeCode)
                ->where('ea.attribute_code IN (?)', $attributeCodes)
        );

        if (!$rows) {
            return null;
        }

        foreach ($attributeCodes as $preferredCode) {
            foreach ($rows as $row) {
                if ((string)($row['attribute_code'] ?? '') === $preferredCode) {
                    return $row;
                }
            }
        }

        return null;
    }

    private function getEntityBackendTable(string $entityTypeCode, string $backendType): string
    {
        $type = strtolower(trim($backendType));
        if ($type === '' || $type === 'static') {
            return '';
        }

        return $entityTypeCode . '_entity_' . $type;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function sortRowsByKey(array $rows, string $key, string $direction = 'desc'): array
    {
        usort($rows, function (array $left, array $right) use ($key, $direction): int {
            $comparison = $this->compareValues($left[$key] ?? null, $right[$key] ?? null);
            return strtolower($direction) === 'asc' ? $comparison : -$comparison;
        });

        return $rows;
    }

    /**
     * @param mixed $left
     * @param mixed $right
     */
    private function compareValues($left, $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return (float)$left <=> (float)$right;
        }

        $leftString = trim((string)$left);
        $rightString = trim((string)$right);
        $leftTime = $leftString !== '' ? strtotime($leftString) : false;
        $rightTime = $rightString !== '' ? strtotime($rightString) : false;
        if ($leftTime !== false && $rightTime !== false) {
            return $leftTime <=> $rightTime;
        }

        return strcasecmp($leftString, $rightString);
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
}