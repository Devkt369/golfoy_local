<?php
namespace ArtM\BrandInventory\Model\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;

class InventorySyncService
{
    protected $resource;
    protected $sourceItemFactory;
    protected $sourceItemsSave;
    protected $sourceItemRepository;
    protected $searchCriteriaBuilder;
     protected $productRepository;

    public function __construct(
        ResourceConnection $resource,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
         ProductRepositoryInterface $productRepository
    ) {
        $this->resource = $resource;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
    }

    public function syncBatch($batchId, $force = false)
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName('artm_brandinventory_temp');
        $batchTable = $this->resource->getTableName('artm_brand_inventory');

        // Optional: prevent re-syncing an already archived batch
        $alreadySynced = (int) $connection->fetchOne(
            "SELECT synced_inventory FROM {$batchTable} WHERE id = ?",
            [$batchId]
        );
        if ($alreadySynced === 1) {
            return [
                'success' => false,
                'status'  => 'info',
                'message' => 'This batch has already been synced and archived.',
                'total'   => 0,
                'updated' => 0,
                'failed'  => 0,
                'failed_items' => []
            ];
        }

        $sourceCode = $connection->fetchOne(
            "SELECT source_code FROM {$batchTable} WHERE id = ?",
            [$batchId]
        );

        if (empty($sourceCode)) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Source code not found for batch ID: ' . $batchId,
                'total' => 0,
                'updated' => 0,
                'failed' => 0,
                'failed_items' => []
            ];
        }

        // Fetch items that still need syncing
        $items = $connection->fetchAll(
            "SELECT entity_id, potential_sku, linked_sku, update_qty 
            FROM {$table} 
            WHERE batch_id = ? 
            AND linked_sku IS NOT NULL 
            AND linked_sku != ''
            AND update_qty >= 0
            AND (inventory_sync = 0 OR inventory_sync IS NULL)",
            [$batchId]
        );

        $updated = 0;
        $failed = 0;
        $failedItems = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $result = $this->updateProductStock($item, $sourceCode);

                if ($result['success']) {
                    $updated++;
                    $this->markSkuSynced($item['entity_id']);
                } else {
                    $failed++;
                    $failedItems[] = $item['linked_sku'] . ' (' . $result['message'] . ')';
                    $this->markSkuFailed($item['entity_id'], $result['message']);
                }
            }
        }

        // ============================================
        // 1. GENERATE REPORT (while temp data exists)
        // ============================================
        $reportFile = null;
        $totalRows = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} WHERE batch_id = ?",
            [$batchId]
        );
        if ($totalRows > 0) {
            $reportFile = $this->generateReport($batchId);
        }

        // ============================================
        // 2. FINALIZE: flag synced + archive report
        // ============================================
        $batchUpdateData = ['synced_inventory' => 1];
        if ($reportFile) {
            $batchUpdateData['report_file'] = $reportFile;
        }
        $connection->update($batchTable, $batchUpdateData, ['id = ?' => $batchId]);

        // 3. DELETE ALL temp records for this batch
        $connection->delete($table, ['batch_id = ?' => $batchId]);

        // 4. Clear synced rows from temp (your original logic)
        $this->clearBatchLogs($batchId);

        // Build response
        if (empty($items)) {
            return [
                'success'      => true,
                'status'       => 'info',
                'message'      => 'No unsynced items remaining. Batch archived and temp data cleared.',
                'total'        => 0,
                'updated'      => 0,
                'failed'       => 0,
                'failed_items' => [],
                'report_file'  => $reportFile
            ];
        }

        $allFailed = ($failed > 0 && $updated === 0);
        $success   = !$allFailed;

        return [
            'success'      => $success,
            'status'       => $success ? 'success' : 'partial',
            'message'      => $success
                ? 'Inventory sync complete. Batch archived and temp data cleared.'
                : 'Inventory sync attempted with failures. Batch archived and temp data cleared. Check report for details.',
            'total'        => count($items),
            'updated'      => $updated,
            'failed'       => $failed,
            'failed_items' => $failedItems,
            'report_file'  => $reportFile
        ];
    }

    // public function syncBatch($batchId, $force = false)
    // {
    //     // Prevent PHP timeout for large batches (5 minutes max)
    //     if (function_exists('set_time_limit')) {
    //         @set_time_limit(300);
    //     }

    //     $connection = $this->resource->getConnection();
    //     $tempTable  = $this->resource->getTableName('artm_brandinventory_temp');
    //     $batchTable = $this->resource->getTableName('artm_brand_inventory');

    //     // 1. Check if already archived
    //     $alreadySynced = (int) $connection->fetchOne(
    //         "SELECT synced_inventory FROM {$batchTable} WHERE id = ?",
    //         [$batchId]
    //     );

    //     if ($alreadySynced === 1 && !$force) {
    //         return [
    //             'success' => false,
    //             'status'  => 'info',
    //             'message' => 'This batch has already been synced and archived.',
    //             'total'   => 0,
    //             'updated' => 0,
    //             'failed'  => 0,
    //             'failed_items' => []
    //         ];
    //     }

    //     // 2. Get source code
    //     $sourceCode = $connection->fetchOne(
    //         "SELECT source_code FROM {$batchTable} WHERE id = ?",
    //         [$batchId]
    //     );

    //     if (empty($sourceCode)) {
    //         return [
    //             'success' => false,
    //             'status'  => 'error',
    //             'message' => 'Source code not found for batch ID: ' . $batchId,
    //             'total'   => 0,
    //             'updated' => 0,
    //             'failed'  => 0,
    //             'failed_items' => []
    //         ];
    //     }

    //     // 3. Count how many remain to sync
    //     $totalPending = (int) $connection->fetchOne(
    //         "SELECT COUNT(*) FROM {$tempTable} 
    //         WHERE batch_id = ? 
    //         AND linked_sku IS NOT NULL 
    //         AND linked_sku != ''
    //         AND update_qty >= 0
    //         AND (inventory_sync = 0 OR inventory_sync IS NULL)",
    //         [$batchId]
    //     );

    //     if ($totalPending === 0) {
    //         $this->finalizeBatch($batchId);
    //         return [
    //             'success' => true,
    //             'status'  => 'info',
    //             'message' => 'No unsynced items remaining. Batch archived.',
    //             'total'   => 0,
    //             'updated' => 0,
    //             'failed'  => 0,
    //             'failed_items' => []
    //         ];
    //     }
    //  // 4. CONFIG
    //     $chunkSize = 50; // Conservative: 50 items per chunk
    //     $updated   = 0;
    //     $failed    = 0;
    //     $failedItems = [];
    //     $offset    = 0;
    //     $hasMore   = true;

    //     try {
    //         while ($hasMore) {
    //             // 5. FETCH CHUNK (memory-safe: never loads all 2000 rows at once)
    //             $items = $connection->fetchAll(
    //                 "SELECT entity_id, linked_sku, update_qty 
    //                 FROM {$tempTable} 
    //                 WHERE batch_id = ? 
    //                 AND linked_sku IS NOT NULL 
    //                 AND linked_sku != ''
    //                 AND update_qty >= 0
    //                 AND (inventory_sync = 0 OR inventory_sync IS NULL)
    //                 ORDER BY entity_id ASC
    //                 LIMIT {$chunkSize} OFFSET {$offset}",
    //                 [$batchId]
    //             );

    //             if (empty($items)) {
    //                 break;
    //             }

    //             $chunkSyncedIds = [];
    //             $chunkFailedIds = [];
    //             $chunkFailedMessages = [];
    //             $sourceItems = [];

    //             // 6. VALIDATE & BUILD chunk
    //             foreach ($items as $item) {
    //                 $sku = $item['linked_sku'];

    //                 try {
    //                     $this->productRepository->get($sku);
    //                 } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
    //                     $failed++;
    //                     $failedItems[] = $sku . ' (SKU not found in catalog)';
    //                     $chunkFailedIds[] = $item['entity_id'];
    //                     $chunkFailedMessages[$item['entity_id']] = 'SKU not found in catalog: ' . $sku;
    //                     continue;
    //                 } catch (\Exception $e) {
    //                     $failed++;
    //                     $failedItems[] = $sku . ' (Validation error: ' . $e->getMessage() . ')';
    //                     $chunkFailedIds[] = $item['entity_id'];
    //                     $chunkFailedMessages[$item['entity_id']] = 'Validation error: ' . $e->getMessage();
    //                     continue;
    //                 }

    //                 try {
    //                     $sourceItem = $this->sourceItemFactory->create();
    //                     $sourceItem->setSku($sku);
    //                     $sourceItem->setSourceCode($sourceCode);
    //                     $sourceItem->setQuantity((float)$item['update_qty']);
    //                     $sourceItem->setStatus($item['update_qty'] > 0 ? 1 : 0);
    //                     $sourceItems[] = $sourceItem;
    //                     $chunkSyncedIds[] = $item['entity_id'];
    //                 } catch (\Exception $e) {
    //                     $failed++;
    //                     $failedItems[] = $sku . ' (Build error: ' . $e->getMessage() . ')';
    //                     $chunkFailedIds[] = $item['entity_id'];
    //                     $chunkFailedMessages[$item['entity_id']] = 'Build error: ' . $e->getMessage();
    //                 }
    //             }

    //             // 7. BATCH SAVE inventory for this chunk (1 API call per 50 items)
    //             if (!empty($sourceItems)) {
    //                 try {
    //                     $this->sourceItemsSave->execute($sourceItems);
    //                     $updated += count($chunkSyncedIds);
    //                 } catch (\Exception $e) {
    //                     $this->logger->error('Chunk save failed: ' . $e->getMessage());
    //                     // If chunk fails, mark all as failed
    //                     $failed += count($chunkSyncedIds);
    //                     foreach ($chunkSyncedIds as $id) {
    //                         $sku = $items[array_search($id, array_column($items, 'entity_id'))]['linked_sku'] ?? 'Unknown';
    //                         $failedItems[] = $sku . ' (Chunk save failed: ' . $e->getMessage() . ')';
    //                         $chunkFailedIds[] = $id;
    //                         $chunkFailedMessages[$id] = 'Chunk save failed: ' . $e->getMessage();
    //                     }
    //                     $chunkSyncedIds = [];
    //                 }
    //             }

    //             // 8. BULK UPDATE temp table - mark synced
    //             if (!empty($chunkSyncedIds)) {
    //                 $connection->update(
    //                     $tempTable,
    //                     [
    //                         'inventory_sync' => 1,
    //                         'inventory_synced_at' => date('Y-m-d H:i:s'),
    //                         'comments' => null
    //                     ],
    //                     ['entity_id IN (?)' => $chunkSyncedIds]
    //                 );
    //             }

    //             // 9. BULK UPDATE temp table - mark failed with specific error
    //             if (!empty($chunkFailedIds)) {
    //                 foreach ($chunkFailedIds as $failedId) {
    //                     $connection->update(
    //                         $tempTable,
    //                         ['comments' => ($chunkFailedMessages[$failedId] ?? 'Sync failed')],
    //                         ['entity_id = ?' => $failedId]
    //                     );
    //                 }
    //             }

    //             $offset += $chunkSize;
    //             if (count($items) < $chunkSize) {
    //                 $hasMore = false;
    //             }
    //         }

    //         // 10. GENERATE REPORT while temp data still exists
    //         $reportFile = $this->generateReport($batchId);

    //         // 11. FINALIZE: flag + clear temp
    //         $this->finalizeBatch($batchId, $reportFile);

    //     } catch (\Exception $e) {
    //         $this->logger->error('syncBatch fatal error: ' . $e->getMessage());
    //         $reportFile = $this->generateReport($batchId);
    //         return [
    //             'success'      => false,
    //             'status'       => 'error',
    //             'message'      => 'Fatal error: ' . $e->getMessage() . '. Partial progress was saved; re-run to continue.',
    //             'total'        => $totalPending,
    //             'updated'      => $updated,
    //             'failed'       => $failed,
    //             'failed_items' => $failedItems,
    //             'report_file'  => $reportFile
    //         ];
    //     }

    //     $allFailed = ($failed > 0 && $updated === 0);

    //     return [
    //         'success'      => !$allFailed,
    //         'status'       => $allFailed ? 'error' : ($failed > 0 ? 'partial' : 'success'),
    //         'message'      => $allFailed
    //             ? 'All items failed. Check report for details.'
    //             : "Sync complete. Updated: {$updated}, Failed: {$failed} of {$totalPending}",
    //         'total'        => $totalPending,
    //         'updated'      => $updated,
    //         'failed'       => $failed,
    //         'failed_items' => $failedItems,
    //         'report_file'  => $reportFile
    //     ];
    // }

    /**
     * Finalize batch: archive flag + delete temp data
     */
    private function finalizeBatch($batchId, $reportFile = null)
    {
        $connection = $this->resource->getConnection();
        $batchTable = $this->resource->getTableName('artm_brand_inventory');
        $tempTable  = $this->resource->getTableName('artm_brandinventory_temp');

        $update = ['synced_inventory' => 1];
        if ($reportFile) {
            $update['report_file'] = $reportFile;
        }

        $connection->update($batchTable, $update, ['id = ?' => $batchId]);
        $connection->delete($tempTable, ['batch_id = ?' => $batchId]);
    }

    public function updateProductStock($item, $sourceCode)
    {
        // NEW: Validate SKU exists in Magento catalog
        try {
            $this->productRepository->get($item['linked_sku']);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return [
                'success' => false,
                'message' => 'SKU not found in catalog: ' . $item['linked_sku']
            ];
        }

        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('sku', $item['linked_sku'])
                ->addFilter('source_code', $sourceCode)
                ->create();

            $sourceItemList = $this->sourceItemRepository->getList($searchCriteria);
            $sourceItems = $sourceItemList->getItems();

            if (!empty($sourceItems)) {
                $sourceItem = reset($sourceItems);
            } else {
                // Product exists but not yet assigned to this source — safe to create
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSku($item['linked_sku']);
                $sourceItem->setSourceCode($sourceCode);
            }

            $sourceItem->setQuantity($item['update_qty']);
            $sourceItem->setStatus($item['update_qty'] > 0 ? 1 : 0);

            $this->sourceItemsSave->execute([$sourceItem]);

            return ['success' => true, 'message' => 'updated for source: ' . $sourceCode];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function markSkuSynced($entityId)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_temp');

        $connection->update($table, [
            'inventory_sync' => 1,
            'inventory_synced_at' => date('Y-m-d H:i:s'),
            'comments' => null   // NEW: wipe old failure comment on success
        ], ['entity_id = ?' => $entityId]);
    }

     private function markSkuFailed($entityId, $message)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_temp');
        $connection->update($table, [
            'comments' => $message
        ], ['entity_id = ?' => $entityId]);
    }

    /**
     * Generate batch report CSV and save to disk
     */
    public function generateReport($batchId)
    {
        try {
            $connection = $this->resource->getConnection();
            $tempTable = $this->resource->getTableName('artm_brandinventory_temp');

            $rows = $connection->fetchAll(
                "SELECT entity_id, product_data, size, qty, style_code, update_qty, color, 
                        potential_sku, linked_sku, match_status, inventory_sync, comments, created_at 
                 FROM {$tempTable} 
                 WHERE batch_id = ? 
                 ORDER BY entity_id DESC",
                [$batchId]
            );

            if (empty($rows)) {
                return null;
            }

            $csvContent = "ID,Style Group,Brand SKU,Gender,Size,Brand Qty,Update Qty,Color,Potential SKU,Linked Magento SKU,Match Status,Inventory Synced,Comments,Created At\n";

            foreach ($rows as $row) {
                $styleGroup = '-';
                $styleCode = $row['style_code'] ?: '-';
                $gender = '-';

                if ($row['product_data']) {
                    try {
                        $pd = json_decode($row['product_data'], true);
                        if ($styleCode === '-' || $styleCode === '') {
                            $styleCode = $pd['style'] ?? $pd['Style'] ?? '-';
                        }
                        $gender = $pd['gender'] ?? $pd['Gender'] ?? '-';
                        
                        // Look for StyleGroup key specifically
                        foreach ($pd as $key => $value) {
                            if (strtolower(str_replace(' ', '', $key)) === 'stylegroup') {
                                $styleGroup = $value;
                                break;
                            }
                        }
                    } catch (\Exception $e) {}
                }

                $potentialSkus = $row['potential_sku'] ?? '-';
                try {
                    $decoded = json_decode($potentialSkus, true);
                    if (is_array($decoded)) {
                        $potentialSkus = implode(' | ', $decoded);
                    }
                } catch (\Exception $e) {}

                $escapeCsv = function($str) {
                    $str = str_replace('"', '""', (string)$str);
                    return '"' . $str . '"';
                };

                $csvContent .= implode(',', [
                    $escapeCsv($row['entity_id']),
                    $escapeCsv($styleGroup),
                    $escapeCsv($styleCode),
                    $escapeCsv($gender),
                    $escapeCsv($row['size'] ?? '-'),
                    $escapeCsv($row['qty'] ?? '0'),
                    $escapeCsv($row['update_qty'] ?? '0'),
                    $escapeCsv($row['color'] ?? '-'),
                    $escapeCsv($potentialSkus),
                    $escapeCsv($row['linked_sku'] ?? ''),
                    $escapeCsv($row['match_status'] ?? 'pending'),
                    $escapeCsv($row['inventory_sync'] ? 'Yes' : 'No'),
                    $escapeCsv($row['comments'] ?? ''),
                    $escapeCsv($row['created_at'] ?? '')
                ]) . "\n";
            }

            $reportsDir = BP . '/var/import/Brand_sheets/reports';
            if (!is_dir($reportsDir)) {
                mkdir($reportsDir, 0777, true);
            }

            $fileName = 'batch_report_' . $batchId . '_' . date('Ymd_His') . '.csv';
            $filePath = $reportsDir . '/' . $fileName;
            file_put_contents($filePath, $csvContent);

            return $fileName;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear all log entries for this batch
     */
    private function clearBatchLogs($batchId)
    {
        try {
            $connection = $this->resource->getConnection();
            $logTable = $this->resource->getTableName('artm_brandinventory_temp');
            $connection->delete(
                $logTable,
                [
                    'batch_id = ?' => $batchId,
                    'inventory_sync = ?' => 1   // ONLY delete synced rows
                ]
            );
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}