<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class Listing extends Action
{
    protected $resultJsonFactory;
    protected $resource;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
    }

    public function execute()
    {
        return $this->listingAction();
    }

    public function listingAction()
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            $connection = $this->resource->getConnection();
            $batchTable = $this->resource->getTableName('artm_brand_inventory');
            $tempTable  = $this->resource->getTableName('artm_brandinventory_temp');

            $batches = $connection->fetchAll(
                "SELECT id, file_path, source_code, uploaded_by, sheet_column, created_at, synced_inventory, report_file 
                FROM {$batchTable} 
                ORDER BY id DESC"
            );

            $data = [];
            foreach ($batches as $batch) {
                $batchId = $batch['id'];
                $isInventorySynced = (int)$batch['synced_inventory'] === 1;

                if ($isInventorySynced) {
                    // ✅ Temp table deleted — read archived counts from record_status
                    $recordStatusJson = $connection->fetchOne(
                        "SELECT record_status FROM {$batchTable} WHERE id = ?",
                        [$batchId]
                    );

                    $counts  = json_decode($recordStatusJson, true) ?: [];
                    $total   = (int) ($counts['total'] ?? 0);
                    $matched = (int) ($counts['matched'] ?? 0);
                    $pending = (int) ($counts['pending'] ?? 0);
                    $synced  = $matched; // All matched items were synced
                } else {
                    // ✅ Temp table still exists — calculate live
                    $total = (int)$connection->fetchOne(
                        "SELECT COUNT(*) FROM {$tempTable} WHERE batch_id = ?",
                        [$batchId]
                    );

                    $matched = (int)$connection->fetchOne(
                        "SELECT COUNT(*) FROM {$tempTable} 
                        WHERE batch_id = ? AND linked_sku IS NOT NULL AND linked_sku != ''",
                        [$batchId]
                    );

                    $pending = $total - $matched;

                    $synced = (int)$connection->fetchOne(
                        "SELECT COUNT(*) FROM {$tempTable} 
                        WHERE batch_id = ? AND inventory_sync = 1",
                        [$batchId]
                    );
                }

                // Status badge logic
                if ($total === 0) {
                    $status = 'Unsynced';
                } elseif ($isInventorySynced) {
                    $status = 'Complete';
                } elseif ($matched === $total) {
                    $status = 'Sku Synced';
                } elseif ($matched > 0) {
                    $status = 'Partially Synced';
                } else {
                    $status = 'Pending';
                }

                $sheetColumns = [];
                if (!empty($batch['sheet_column'])) {
                    $decoded = json_decode($batch['sheet_column'], true);
                    $sheetColumns = is_array($decoded) ? $decoded : [];
                }

                $data[] = [
                    'id'               => $batchId,
                    'file'             => basename($batch['file_path']),
                    'source'           => $batch['source_code'],
                    'user'             => $batch['uploaded_by'],
                    'date'             => $batch['created_at'],
                    'total'            => $total,
                    'matched'          => $matched,
                    'pending'          => $pending,
                    'synced'           => $synced,
                    'synced_inventory' => (int)$batch['synced_inventory'],
                    'status'           => $status,
                    'sheet_columns'    => $sheetColumns
                ];
            }

            return $resultJson->setData(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    protected function _isAllowed()
    {
        return true;
    }
}