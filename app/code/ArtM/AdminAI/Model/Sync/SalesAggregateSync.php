<?php
namespace ArtM\AdminAI\Model\Sync;

use ArtM\AdminAI\Api\SyncStateServiceInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class SalesAggregateSync
{
    private const SYNC_CODE = 'sales_aggregate';

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
     * @return int Number of upserted aggregate rows
     */
    public function execute(): int
    {
        $state = $this->syncStateService->getState(self::SYNC_CODE);
        $lastSyncedAt = $state['last_synced_at'] ?? null;

        $this->syncStateService->setStatus(self::SYNC_CODE, 'running', 'Sales aggregate sync in progress');

        try {
            $conn = $this->resource->getConnection();
            $orderTable = $this->resource->getTableName('sales_order');
            $customerTable = $this->resource->getTableName('customer_entity');
            $targetTable = $this->resource->getTableName('artm_adminai_sales_aggregate_day');

            $affectedDates = $this->fetchAffectedDates($conn, $orderTable, $lastSyncedAt);
            if (empty($affectedDates)) {
                $now = gmdate('Y-m-d H:i:s');
                $this->syncStateService->saveState(self::SYNC_CODE, $now, null, 'idle', 'No new sales rows to sync');
                return 0;
            }

            $upserted = 0;
            foreach ($affectedDates as $date) {
                $rows = $conn->fetchAll(
                    $conn->select()
                        ->from(
                            ['o' => $orderTable],
                            [
                                'aggregate_date' => new \Zend_Db_Expr('DATE(o.created_at)'),
                                'store_id' => 'o.store_id',
                                'orders_count' => new \Zend_Db_Expr('COUNT(*)'),
                                'revenue_total' => new \Zend_Db_Expr('SUM(o.base_grand_total)'),
                                'items_qty_total' => new \Zend_Db_Expr('SUM(o.total_qty_ordered)'),
                                'new_customers_count' => new \Zend_Db_Expr('COUNT(DISTINCT CASE WHEN ce.entity_id IS NOT NULL AND DATE(ce.created_at) = DATE(o.created_at) THEN ce.entity_id END)')
                            ]
                        )
                        ->joinLeft(['ce' => $customerTable], 'ce.entity_id = o.customer_id', [])
                        ->where('DATE(o.created_at) = ?', $date)
                        ->where('o.state NOT IN (?)', ['canceled', 'closed'])
                        ->group(['DATE(o.created_at)', 'o.store_id'])
                );

                foreach ($rows as $row) {
                    $conn->insertOnDuplicate(
                        $targetTable,
                        [
                            'aggregate_date' => (string)$row['aggregate_date'],
                            'store_id' => (int)$row['store_id'],
                            'orders_count' => (int)$row['orders_count'],
                            'revenue_total' => (float)$row['revenue_total'],
                            'items_qty_total' => (float)$row['items_qty_total'],
                            'new_customers_count' => (int)$row['new_customers_count'],
                        ],
                        ['orders_count', 'revenue_total', 'items_qty_total', 'new_customers_count']
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
                'Sales aggregate sync complete: ' . $upserted . ' rows'
            );

            return $upserted;
        } catch (\Throwable $e) {
            $this->syncStateService->setStatus(self::SYNC_CODE, 'failed', 'Sales aggregate sync failed');
            $this->logger->error('SalesAggregateSync failed', ['exception' => $e]);
            throw $e;
        }
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
