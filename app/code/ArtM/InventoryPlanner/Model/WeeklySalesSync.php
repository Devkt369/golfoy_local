<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Model;

use ArtM\InventoryPlanner\Model\ResourceModel\WeeklySales as WeeklySalesResource;
use Magento\Framework\App\ResourceConnection;

/**
 * Single shared place for "aggregate qty sold per SKU per week, then persist it" -
 * used by both the Sync Data page (Calculate) and the Settings page (Sync Now), so
 * the query and the upsert logic aren't duplicated between the two.
 *
 * Weeks are always calendar weeks, Monday through Sunday, computed in India time
 * (orders are stored in UTC) - regardless of which entry point triggered the sync,
 * so the same week always lands in the same week_start row.
 */
class WeeklySalesSync
{
    private const IST_OFFSET = '+05:30';

    private const IST_TIMEZONE = 'Asia/Kolkata';

    private ResourceConnection $resourceConnection;

    private WeeklySalesResource $weeklySalesResource;

    private InventorySnapshotUpdater $inventorySnapshotUpdater;

    public function __construct(
        ResourceConnection $resourceConnection,
        WeeklySalesResource $weeklySalesResource,
        InventorySnapshotUpdater $inventorySnapshotUpdater
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->weeklySalesResource = $weeklySalesResource;
        $this->inventorySnapshotUpdater = $inventorySnapshotUpdater;
    }

    /**
     * Manual recompute for a date range (the Sync Data page's "Calculate" button).
     * Idempotent: running the same range twice replaces qty_sold rather than adding
     * to it, so re-running it to fix/refresh data is safe.
     *
     * @param string[] $statuses
     * @return array{rows: array<int, array<string, mixed>>, stats: array<string, mixed>}
     */
    public function syncRange(\DateTimeImmutable $from, \DateTimeImmutable $to, array $statuses): array
    {
        if (!$statuses || $from > $to) {
            return ['rows' => [], 'stats' => $this->emptyStats()];
        }

        $fromCondition = 'order.created_at >= ?';
        $fromValue = $from->format('Y-m-d 00:00:00');
        $toCondition = 'order.created_at <= ?';
        $toValue = $to->format('Y-m-d 23:59:59');

        $rows = $this->aggregate($fromCondition, $fromValue, $toCondition, $toValue, $statuses);
        $this->weeklySalesResource->upsertRows($rows, false);
        $this->inventorySnapshotUpdater->updateForProducts($this->distinctProducts($rows));

        $stats = $this->buildStats($rows, $fromCondition, $fromValue, $toCondition, $toValue, $statuses);

        return ['rows' => $rows, 'stats' => $stats];
    }

    /**
     * Incremental sync for the Settings page's "Sync Now" button: resumes from the
     * last order id already synced (instead of a date), so it always picks up every
     * order placed since, and merges into a still-open week's total instead of
     * overwriting it.
     *
     * @param string[] $statuses
     * @return array{rows: array<int, array<string, mixed>>, last_order_id: int, stats: array<string, mixed>}
     */
    public function syncFromOrderId(int $lastOrderId, array $statuses): array
    {
        if (!$statuses) {
            return ['rows' => [], 'last_order_id' => $lastOrderId, 'stats' => $this->emptyStats()];
        }

        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');

        // Cap the batch at "orders that exist right now" so the aggregation query and
        // the cursor we advance to agree on exactly which orders were considered.
        $maxOrderId = (int) $connection->fetchOne(
            $connection->select()
                ->from($orderTable, ['max_id' => new \Zend_Db_Expr('MAX(entity_id)')])
                ->where('entity_id > ?', $lastOrderId)
        );

        if ($maxOrderId <= $lastOrderId) {
            return ['rows' => [], 'last_order_id' => $lastOrderId, 'stats' => $this->emptyStats()];
        }

        $fromCondition = 'order.entity_id > ?';
        $fromValue = $lastOrderId;
        $toCondition = 'order.entity_id <= ?';
        $toValue = $maxOrderId;

        $rows = $this->aggregate($fromCondition, $fromValue, $toCondition, $toValue, $statuses);
        $this->weeklySalesResource->upsertRows($rows, true);
        $this->inventorySnapshotUpdater->updateForProducts($this->distinctProducts($rows));

        $stats = $this->buildStats($rows, $fromCondition, $fromValue, $toCondition, $toValue, $statuses);

        return ['rows' => $rows, 'last_order_id' => $maxOrderId, 'stats' => $stats];
    }

    /**
     * One entry per distinct product that appears in $rows - a product can appear in
     * several rows (one per week), but its inventory only needs snapshotting once.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{product_id:int, sku:string}>
     */
    private function distinctProducts(array $rows): array
    {
        $products = [];
        foreach ($rows as $row) {
            $products[$row['product_id']] = ['product_id' => $row['product_id'], 'sku' => $row['sku']];
        }

        return array_values($products);
    }

    /**
     * Single grouped query: buckets every matching order item into its India-time
     * calendar week (Monday-Sunday) directly in SQL, rather than running one query
     * per week or loading orders one by one. $fromCondition/$toCondition are the two
     * boundary WHERE clauses (either a created_at date range, or an entity_id range)
     * that scope which orders are included.
     *
     * @param string[] $statuses
     * @return array<int, array<string, mixed>>
     */
    private function aggregate(
        string $fromCondition,
        $fromValue,
        string $toCondition,
        $toValue,
        array $statuses
    ): array {
        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');
        $itemTable = $this->resourceConnection->getTableName('sales_order_item');

        $istCreatedAt = "CONVERT_TZ(order.created_at, '+00:00', '" . self::IST_OFFSET . "')";

        $select = $connection->select()
            ->from(['item' => $itemTable], [
                'product_id' => 'item.product_id',
                'sku' => 'item.sku',
                // Monday of the India-time calendar week the order falls in.
                'week_start' => new \Zend_Db_Expr(
                    "DATE_SUB(DATE({$istCreatedAt}), INTERVAL WEEKDAY({$istCreatedAt}) DAY)"
                ),
                'qty_sold' => new \Zend_Db_Expr('SUM(item.qty_ordered)'),
                'item_count' => new \Zend_Db_Expr('COUNT(item.item_id)'),
            ])
            ->joinInner(['order' => $orderTable], 'order.entity_id = item.order_id', [])
            ->where('order.status IN (?)', $statuses)
            ->where($fromCondition, $fromValue)
            ->where($toCondition, $toValue)
            ->where('item.product_id IS NOT NULL')
            // Exclude configurable PARENT rows only. A configurable purchase creates both a
            // parent item (product_id = the configurable, no stock of its own) and a child
            // item (product_id = the actual simple SKU) - keep the child, skip the parent,
            // so real SKUs aren't dropped and quantities aren't double counted.
            ->where('item.product_type != ?', 'configurable')
            ->group(['item.product_id', 'item.sku', 'week_start'])
            ->having('SUM(item.qty_ordered) > 0')
            ->order(['item.product_id ASC', 'week_start ASC']);

        $rows = [];
        foreach ($connection->fetchAll($select) as $row) {
            $weekStart = new \DateTimeImmutable((string) $row['week_start']);

            $rows[] = [
                'product_id' => (int) $row['product_id'],
                'sku' => (string) $row['sku'],
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekStart->modify('+6 days')->format('Y-m-d'),
                'qty_sold' => (float) $row['qty_sold'],
                'item_count' => (int) $row['item_count'],
            ];
        }

        return $rows;
    }

    /**
     * Order-level stats for the range just synced: how many distinct orders it
     * covered, the first/last order id + date in that range, plus item/SKU counts
     * derived from the already-aggregated rows.
     *
     * @param string[] $statuses
     */
    private function buildStats(
        array $rows,
        string $fromCondition,
        $fromValue,
        string $toCondition,
        $toValue,
        array $statuses
    ): array {
        $connection = $this->resourceConnection->getConnection();
        $orderTable = $this->resourceConnection->getTableName('sales_order');

        // Scanned = every order in the range, regardless of status - this is the
        // actual window "Current syncing started from ... to ..." describes.
        $scannedSelect = $connection->select()
            ->from(['order' => $orderTable], [
                'total_scanned_orders' => new \Zend_Db_Expr('COUNT(DISTINCT order.entity_id)'),
                'from_order_id' => new \Zend_Db_Expr('MIN(order.entity_id)'),
                'to_order_id' => new \Zend_Db_Expr('MAX(order.entity_id)'),
                'from_order_date' => new \Zend_Db_Expr('MIN(order.created_at)'),
                'to_order_date' => new \Zend_Db_Expr('MAX(order.created_at)'),
            ])
            ->where($fromCondition, $fromValue)
            ->where($toCondition, $toValue);
        $scannedRow = $connection->fetchRow($scannedSelect);

        // Synced = only orders whose status is one of the selected ones - the subset
        // that actually contributed to $rows / qty_sold. Orders with any other status
        // are scanned but never counted, here or in the aggregate() query itself.
        $totalSyncedOrders = (int) $connection->fetchOne(
            $connection->select()
                ->from(['order' => $orderTable], ['cnt' => new \Zend_Db_Expr('COUNT(DISTINCT order.entity_id)')])
                ->where('order.status IN (?)', $statuses)
                ->where($fromCondition, $fromValue)
                ->where($toCondition, $toValue)
        );

        $totalItemIds = 0;
        $skus = [];
        foreach ($rows as $row) {
            $totalItemIds += (int) $row['item_count'];
            $skus[$row['sku']] = true;
        }

        return [
            'total_scanned_orders' => (int) ($scannedRow['total_scanned_orders'] ?? 0),
            'total_synced_orders' => $totalSyncedOrders,
            'from_order_id' => $scannedRow['from_order_id'] !== null ? (int) $scannedRow['from_order_id'] : null,
            'to_order_id' => $scannedRow['to_order_id'] !== null ? (int) $scannedRow['to_order_id'] : null,
            'from_order_date' => $scannedRow['from_order_date'] !== null
                ? $this->toIstDisplay((string) $scannedRow['from_order_date'])
                : null,
            'to_order_date' => $scannedRow['to_order_date'] !== null
                ? $this->toIstDisplay((string) $scannedRow['to_order_date'])
                : null,
            'total_item_ids' => $totalItemIds,
            'total_unique_skus' => count($skus),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyStats(): array
    {
        return [
            'total_scanned_orders' => 0,
            'total_synced_orders' => 0,
            'from_order_id' => null,
            'to_order_id' => null,
            'from_order_date' => null,
            'to_order_date' => null,
            'total_item_ids' => 0,
            'total_unique_skus' => 0,
        ];
    }

    private function toIstDisplay(string $utcDateTime): string
    {
        $date = new \DateTimeImmutable($utcDateTime, new \DateTimeZone('UTC'));

        return $date->setTimezone(new \DateTimeZone(self::IST_TIMEZONE))->format('d-M-Y g:i A');
    }
}
