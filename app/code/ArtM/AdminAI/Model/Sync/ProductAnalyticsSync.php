<?php
namespace ArtM\AdminAI\Model\Sync;

use ArtM\AdminAI\Api\SyncStateServiceInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class ProductAnalyticsSync
{
    private const SYNC_CODE = 'product_analytics';

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
     * @return int Number of upserted analytics rows
     */
    public function execute(): int
    {
        $state = $this->syncStateService->getState(self::SYNC_CODE);
        $lastSyncedAt = $state['last_synced_at'] ?? null;

        $this->syncStateService->setStatus(self::SYNC_CODE, 'running', 'Product analytics sync in progress');

        try {
            $conn = $this->resource->getConnection();
            $orderTable = $this->resource->getTableName('sales_order');
            $itemTable = $this->resource->getTableName('sales_order_item');
            $inventoryTable = $this->resource->getTableName('inventory_source_item');
            $targetTable = $this->resource->getTableName('artm_adminai_product_analytics_day');

            $affectedDates = $this->fetchAffectedDates($conn, $orderTable, $lastSyncedAt);
            if (empty($affectedDates)) {
                $now = gmdate('Y-m-d H:i:s');
                $this->syncStateService->saveState(self::SYNC_CODE, $now, null, 'idle', 'No new product analytics rows to sync');
                return 0;
            }

            $upserted = 0;
            foreach ($affectedDates as $date) {
                $rows = $conn->fetchAll(
                    $conn->select()
                        ->from(
                            ['oi' => $itemTable],
                            [
                                'aggregate_date' => new \Zend_Db_Expr('DATE(o.created_at)'),
                                'product_id' => 'oi.product_id',
                                'sku' => 'oi.sku',
                                'store_id' => 'o.store_id',
                                'qty_sold' => new \Zend_Db_Expr('SUM(oi.qty_ordered)'),
                                'revenue_total' => new \Zend_Db_Expr('SUM(oi.base_row_total_incl_tax)')
                            ]
                        )
                        ->joinInner(['o' => $orderTable], 'o.entity_id = oi.order_id', [])
                        ->where('DATE(o.created_at) = ?', $date)
                        ->where('o.state NOT IN (?)', ['canceled', 'closed'])
                        ->where('oi.parent_item_id IS NULL')
                        ->group(['DATE(o.created_at)', 'oi.product_id', 'oi.sku', 'o.store_id'])
                );

                $skuList = [];
                foreach ($rows as $row) {
                    if (!empty($row['sku'])) {
                        $skuList[] = (string)$row['sku'];
                    }
                }

                $stockBySku = $this->fetchStockBySku($conn, $inventoryTable, $skuList);

                foreach ($rows as $row) {
                    $sku = (string)$row['sku'];
                    $qtySold = (float)$row['qty_sold'];
                    $stockQty = (float)($stockBySku[$sku] ?? 0);
                    $daysOfStock = $qtySold > 0.0001 ? ($stockQty / $qtySold) : null;

                    $conn->insertOnDuplicate(
                        $targetTable,
                        [
                            'aggregate_date' => (string)$row['aggregate_date'],
                            'product_id' => (int)$row['product_id'],
                            'sku' => $sku,
                            'store_id' => (int)$row['store_id'],
                            'qty_sold' => $qtySold,
                            'revenue_total' => (float)$row['revenue_total'],
                            'stock_qty' => $stockQty,
                            'days_of_stock' => $daysOfStock,
                        ],
                        ['qty_sold', 'revenue_total', 'stock_qty', 'days_of_stock']
                    );
                    $upserted++;
                }
            }

            $watermark = $this->fetchWatermark($conn, $orderTable, $lastSyncedAt);
            $this->syncStateService->saveState(
                self::SYNC_CODE,
                $watermark['last_synced_at'] ?? gmdate('Y-m-d H:i:s'),
                $watermark['last_entity_id'] ?? null,
                'idle',
                'Product analytics sync complete: ' . $upserted . ' rows'
            );

            return $upserted;
        } catch (\Throwable $e) {
            $this->syncStateService->setStatus(self::SYNC_CODE, 'failed', 'Product analytics sync failed');
            $this->logger->error('ProductAnalyticsSync failed', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $inventoryTable
     * @param string[] $skus
     * @return array<string,float>
     */
    private function fetchStockBySku($conn, string $inventoryTable, array $skus): array
    {
        $skus = array_values(array_unique(array_filter(array_map('strval', $skus))));
        if (empty($skus)) {
            return [];
        }

        $rows = $conn->fetchAll(
            $conn->select()
                ->from(
                    ['i' => $inventoryTable],
                    [
                        'sku' => 'i.sku',
                        'stock_qty' => new \Zend_Db_Expr('SUM(i.quantity)')
                    ]
                )
                ->where('i.sku IN (?)', $skus)
                ->group('i.sku')
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(string)$row['sku']] = (float)$row['stock_qty'];
        }

        return $result;
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $conn
     * @param string $orderTable
     * @param string|null $lastSyncedAt
     * @return string[]
     */
    private function fetchAffectedDates($conn, string $orderTable, ?string $lastSyncedAt): array
    {
        $select = $conn->select()
            ->from(['o' => $orderTable], ['d' => new \Zend_Db_Expr('DISTINCT DATE(o.created_at)')])
            ->where('o.state NOT IN (?)', ['canceled', 'closed'])
            ->order('d ASC');

        if ($lastSyncedAt !== null && $lastSyncedAt !== '') {
            $select->where('o.created_at > ?', $lastSyncedAt);
        }

        return array_values(array_filter(array_map('strval', $conn->fetchCol($select))));
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
