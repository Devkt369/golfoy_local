<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;
use ArtM\BrandInventory\Model\Service\InventorySyncService;
use ArtM\BrandInventory\Model\Service\BatchHistoryService;
use ArtM\BrandInventory\Model\Service\ManualMatchService;


class Updates extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'ArtM_BrandInventory::inventory';
    
    protected $resource;
    protected $resultPageFactory = false;
    protected $resultJsonFactory;
    protected $inventorySyncService;
    protected $batchHistoryService;
    protected $manualMatchService;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resource,
        InventorySyncService $inventorySyncService,
        BatchHistoryService $batchHistoryService,
        ManualMatchService $manualMatchService
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        $this->inventorySyncService = $inventorySyncService;
        $this->batchHistoryService = $batchHistoryService;
        $this->manualMatchService = $manualMatchService;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $action = $this->getRequest()->getParam('action');
        if ($this->getRequest()->isAjax()) {

            if ($action === 'update') {
                return $this->handleUpdate();
            }
            if ($action === 'remove') {
                return $this->handleRemove();
            }
            if ($action === 'getSelectedAttrs') {
                return $this->handleGetSelectedAttrs();
            }
            if ($action === 'getMatchSummary') {
                return $this->getMatchSummaryAction();
            }
            if ($action === 'getBatchLogs') {
                return $this->getBatchLogs();
            }
            // ✅ NEW: Check for duplicate linked SKU
            if ($action === 'checkDuplicateSku') {
                return $this->handleCheckDuplicateSku();
            }
            if ($action === 'updateManualMatch') {
                return $this->handleUpdateManualMatch();
            }
            if ($action === 'deleteManualMatch') {
                return $this->handleDeleteManualMatch();
            }
            if ($action === 'getFormats') {
                return $this->getFormats();
            }
            if ($action === 'getFormatDetails') {
                return $this->getFormatDetails();
            }

        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Brand Sheet Upload')));
        return $resultPage;
    }

    /**
     * ✅ NEW: Check if linked SKU already exists in same batch
     */
    private function handleCheckDuplicateSku()
    {
        $resultJson = $this->resultJsonFactory->create();
        $batchId = $this->getRequest()->getParam('batch_id');
        $linkedSku = trim($this->getRequest()->getParam('linked_sku', ''));
        $excludeId = $this->getRequest()->getParam('exclude_id');

        if (!$batchId || !$linkedSku) {
            return $resultJson->setData([
                'success' => false,
                'duplicate' => false,
                'message' => 'Missing parameters'
            ]);
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandinventory_temp');

            $select = $connection->select()
                ->from($table, ['entity_id', 'potential_sku', 'linked_sku', 'size', 'color'])
                ->where('batch_id = ?', $batchId)
                ->where('linked_sku = ?', $linkedSku)
                ->where('linked_sku IS NOT NULL')
                ->where("linked_sku != ''");

            if ($excludeId) {
                $select->where('entity_id != ?', $excludeId);
            }

            $existing = $connection->fetchRow($select);

            if ($existing) {
                // Decode potential_sku for readable display
                $potentialSkus = $existing['potential_sku'];
                try {
                    $decoded = json_decode($potentialSkus, true);
                    $potentialSkus = is_array($decoded) ? implode(', ', $decoded) : $potentialSkus;
                } catch (\Exception $e) {
                    // keep as is
                }

                return $resultJson->setData([
                    'success' => false,
                    'duplicate' => true,
                    'message' => "SKU '{$linkedSku}' is already linked to another potential SKU",
                    'existing' => [
                        'entity_id' => $existing['entity_id'],
                        'potential_sku' => $potentialSkus,
                        'linked_sku' => $existing['linked_sku'],
                        'size' => $existing['size'],
                        'color' => $existing['color']
                    ]
                ]);
            }

            return $resultJson->setData([
                'success' => true,
                'duplicate' => false,
                'message' => 'SKU is available'
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'duplicate' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ UPDATED: Handle update with DUPLICATE CHECK
     */
    private function handleUpdate()
    {
        $resultJson = $this->resultJsonFactory->create();
        $id = $this->getRequest()->getParam('id');
        $linkedSku = trim($this->getRequest()->getParam('linked_sku', ''));
        $updateQty = $this->getRequest()->getParam('update_qty');
        //echo $linkedSku; exit;;
        if (!$id) {
            return $resultJson->setData(['success' => false, 'message' => 'ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $tempTable = $this->resource->getTableName('artm_brandinventory_temp');
            $logTable = $this->resource->getTableName('artm_brandinventory_log');
            $mainTable = $this->resource->getTableName('artm_brand_inventory');

            // Get current data + batch_id
            $current = $connection->fetchRow(
                "SELECT batch_id, potential_sku, linked_sku, update_qty FROM {$tempTable} WHERE entity_id = ?",
                [$id]
            );

            if (!$current) {
                return $resultJson->setData(['success' => false, 'message' => 'Record not found']);
            }
            //print_r($current); exit;

            $batchId = $current['batch_id'];
            $oldLinkedSku = $current['linked_sku'];
            $oldUpdateQty = $current['update_qty'];
            $newUpdateQty = ($updateQty !== null && $updateQty !== '') ? (float)$updateQty : null;

            // ✅ DUPLICATE CHECK: If linked_sku is being changed to a new non-empty value
            if ($linkedSku !== '' && $linkedSku !== $oldLinkedSku) {
                $duplicateCheck = $connection->fetchRow(
                    "SELECT entity_id, potential_sku, size, color 
                    FROM {$tempTable} 
                    WHERE batch_id = ? 
                    AND linked_sku = ? 
                    AND entity_id != ? 
                    AND linked_sku IS NOT NULL 
                    AND linked_sku != ''",
                    [$batchId, $linkedSku, $id]
                );

                if ($duplicateCheck) {
                    $potentialSkus = $duplicateCheck['potential_sku'];
                    try {
                        $decoded = json_decode($potentialSkus, true);
                        $potentialSkus = is_array($decoded) ? implode(', ', $decoded) : $potentialSkus;
                    } catch (\Exception $e) {
                        // keep as is
                    }

                    return $resultJson->setData([
                        'success' => false,
                        'duplicate' => true,
                        'message' => "SKU '{$linkedSku}' is already linked to another potential SKU",
                        'existing' => [
                            'entity_id' => $duplicateCheck['entity_id'],
                            'potential_sku' => $potentialSkus,
                            'size' => $duplicateCheck['size'],
                            'color' => $duplicateCheck['color']
                        ]
                    ]);
                }
            }

            $updateData = ['match_status' => 'manually_updated'];

            // Update linked_sku if provided (can be empty string to clear)
            if ($linkedSku !== null) {
                $updateData['linked_sku'] = $linkedSku === '' ? null : $linkedSku;
            }

            // Update update_qty if provided
            if ($newUpdateQty !== null) {
                $updateData['update_qty'] = $newUpdateQty;
            }

            $connection->update($tempTable, $updateData, ['entity_id = ?' => $id]);

            $total = (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM {$tempTable} WHERE batch_id = ?",
                [$batchId]
            );

            $matched = (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM {$tempTable} WHERE batch_id = ? AND linked_sku IS NOT NULL AND linked_sku != ''",
                [$batchId]
            );

            $pending = $total - $matched;
                $statusData = [
                'total'     => $total,
                'matched'   => $matched,
                'pending'   => $pending,
                'synced_at' => date('Y-m-d H:i:s')
            ];
            $statusJson = json_encode($statusData);

            // If the batch row doesn't exist yet in main table, insert it
            $rowExists = (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM {$mainTable} WHERE id = ?",
                [$batchId]
            );

            if (!$rowExists) {
                $connection->insert($mainTable, [
                    'id'      => $batchId,
                    'record_status' => $statusJson,
                    'created_at'    => date('Y-m-d H:i:s')
                    // add 'updated_at' => date('Y-m-d H:i:s') only if that column exists
                ]);
            } else {
                $connection->update(
                    $mainTable,
                    ['record_status' => $statusJson],
                    ['id = ?' => $batchId]
                );
            }

            $adminUser = $this->_auth->getUser();
            $updatedBy = $adminUser ? $adminUser->getUsername() : 'system';

            // ✅ LOG: Linked SKU change
            if ($linkedSku !== null && $linkedSku !== $oldLinkedSku) {
                $connection->insert($logTable, [
                    'temp_id' => $id,
                    'batch_id' => $batchId,
                    'potential_sku' => $current['potential_sku'],
                    'old_linked_sku' => $oldLinkedSku,
                    'new_linked_sku' => $linkedSku,
                    'old_update_qty' => $oldUpdateQty,
                    'new_update_qty' => $newUpdateQty ?? $oldUpdateQty,
                    'action_type' => 'manual_update',
                    'updated_by' => $updatedBy,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // ✅ LOG: Inventory change (only if qty actually changed)
            if ($newUpdateQty !== null && (float)$newUpdateQty != (float)$oldUpdateQty) {
                $connection->insert($logTable, [
                    'temp_id' => $id,
                    'batch_id' => $batchId,
                    'potential_sku' => $current['potential_sku'],
                    'old_linked_sku' => $oldLinkedSku,
                    'new_linked_sku' => $linkedSku !== null ? ($linkedSku === '' ? null : $linkedSku) : $oldLinkedSku,
                    'old_update_qty' => $oldUpdateQty,
                    'new_update_qty' => $newUpdateQty,
                    'action_type' => 'inventory_updated',
                    'updated_by' => $updatedBy,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // ✅ AUTO-SYNC: Use final values
            $finalLinkedSku = $linkedSku !== null ? ($linkedSku === '' ? null : $linkedSku) : $oldLinkedSku;
            $finalUpdateQty = $newUpdateQty ?? $oldUpdateQty;

            $syncMessage = '';
            // FIXED: allow 0 to sync to Magento (>= 0 instead of > 0, and !== null instead of !empty)
            if (!empty($finalLinkedSku) && $finalUpdateQty !== null && (float)$finalUpdateQty >= 0) {
                $syncResult = $this->syncSingleItem($id, $finalLinkedSku, (float)$finalUpdateQty);
                if ($syncResult['success']) {
                    $syncMessage = ' & synced to Magento source';
                } else {
                    $syncMessage = ' (sync failed: ' . $syncResult['message'] . ')';
                }
            }

            // ✅ BATCH COMMENT: Record SKU update in batch history
            if ($linkedSku !== $oldLinkedSku) {
                $details = ['sku' => $finalLinkedSku ?: '(cleared)'];
                $this->batchHistoryService->appendComment($batchId, 'sku_updated', $updatedBy, $details);
            }

            // ✅ SAVE MANUAL MATCH: Record manual match to artm_brandinventory_manual_match table
            if ($finalLinkedSku !== null && $finalLinkedSku !== '') {
                $this->recordManualMatch($id, $batchId, $finalLinkedSku);
            }

            return $resultJson->setData([
                'success' => true,
                'message' => 'Updated successfully' . $syncMessage,
                'synced' => !empty($syncMessage) && strpos($syncMessage, 'synced') !== false
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Record a manual match row for a single admin grid update
     */
    private function recordManualMatch($tempId, $batchId, $linkedSku)
    {
        try {
            $connection = $this->resource->getConnection();
            $manualMatchTable = $this->resource->getTableName('artm_brandinventory_manual_match');

            $tempRowData = $this->manualMatchService->getTempRowData($tempId);
            if (!$tempRowData) {
                return;
            }

            $sourceCode = $this->manualMatchService->getBatchSourceCode($batchId);

            $brandSku = preg_replace('/\s+/', '', $tempRowData['brand_sku'] ?? '');
            $color    = preg_replace('/\s+/', '', $tempRowData['color'] ?? '');
            $size     = strtoupper($tempRowData['size'] ?? '');
            
            if($color == null || $size == null){
               $combinedSku = $brandSku;
            }else{
                $combinedSku = $brandSku . '-' . $color . '-' . $size;
            }

            $final_sku = strtoupper(preg_replace('/\s+/', '', $combinedSku ?? ''));

            $brandCreateData = [
                'style_group' => $tempRowData['style_code'] ?? null,
                'brand_sku' => $tempRowData['brand_sku'] ?? null,
                'combined_sku' =>$final_sku,
                'gender' => $tempRowData['gender'] ?? null,
                'color' => $tempRowData['color'] ?? null,
                'size' => $tempRowData['size'] ?? null
            ];

           //echo'<pre>'; print_r($brandCreateData);  die('brand crated data');
            $existingMatch = $connection->fetchRow(
                "SELECT id FROM {$manualMatchTable} WHERE batch_id = ? AND linked_sku = ? LIMIT 1",
                [$batchId, $linkedSku]
            );

            $matchData = [
                'batch_id' => $batchId,
                'linked_sku' => $linkedSku,
                'brand_created_sku' => json_encode($brandCreateData, JSON_UNESCAPED_UNICODE),
                'source_code' => $sourceCode,
                'status' => 'manually_matched',
                'created_date' => date('Y-m-d H:i:s')
            ];

            if ($existingMatch && isset($existingMatch['id'])) {
                $connection->update($manualMatchTable, $matchData, ['id = ?' => $existingMatch['id']]);
            } else {
                $connection->insert($manualMatchTable, $matchData);
            }
        } catch (\Exception $e) {
            // Silent failure: manual match should not break the main update flow
        }
    }

    /**
     * ✅ NEW: Sync single item to Magento source inventory
     */
    private function syncSingleItem($entityId, $linkedSku, $updateQty)
    {  

    //echo "Syncing SKU: $linkedSku with Qty: $updateQty for Entity ID: $entityId"; exit;// Debug log 
        try {
            $connection = $this->resource->getConnection();
            $tempTable = $this->resource->getTableName('artm_brandinventory_temp');
            $batchTable = $this->resource->getTableName('artm_brand_inventory');

            // Get batch_id to find source_code
            $batchId = $connection->fetchOne(
                "SELECT batch_id FROM {$tempTable} WHERE entity_id = ?",
                [$entityId]
            );

            if (!$batchId) {
                return ['success' => false, 'message' => 'Batch not found'];
            }

            $sourceCode = $connection->fetchOne(
                "SELECT source_code FROM {$batchTable} WHERE id = ?",
                [$batchId]
            );

            if (empty($sourceCode)) {
                return ['success' => false, 'message' => 'Source code not found'];
            }

            // Call the sync service for this single item
            $item = [
                'entity_id' => $entityId,
                'linked_sku' => $linkedSku,
                'update_qty' => $updateQty
            ];

            $result = $this->inventorySyncService->updateProductStock($item, $sourceCode);
            //print_r($result); die;// Debug log
            // Mark as synced if successful
            if ($result['success']) {
                //echo 'success';exit;
                $connection->update($tempTable, [
                    'inventory_sync' => 1,
                    'inventory_synced_at' => date('Y-m-d H:i:s')
                ], ['entity_id = ?' => $entityId]);
            }
            //echo'fail ';exit;
            return $result;

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function handleRemove()
    {
        $resultJson = $this->resultJsonFactory->create();
        $id = $this->getRequest()->getParam('id');

        if (!$id) {
            return $resultJson->setData(['success' => false, 'message' => 'ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $tempTable = $this->resource->getTableName('artm_brandinventory_temp');
            $logTable = $this->resource->getTableName('artm_brandinventory_log');

            $current = $connection->fetchRow(
                "SELECT batch_id, potential_sku, linked_sku FROM {$tempTable} WHERE entity_id = ?",
                [$id]
            );

            if (!$current) {
                return $resultJson->setData(['success' => false, 'message' => 'Record not found']);
            }

            $connection->update(
                $tempTable,
                [
                    'linked_sku' => null,
                    'match_status' => 'pending'
                ],
                ['entity_id = ?' => $id]
            );
        // ✅ Save log
            $adminUser = $this->_auth->getUser();
            $updatedBy = $adminUser ? $adminUser->getUsername() : 'system';

            $connection->insert($logTable, [
                'temp_id' => $id,
                'batch_id' => $current['batch_id'],
                'potential_sku' => $current['potential_sku'],
                'old_linked_sku' => $current['linked_sku'],
                'new_linked_sku' => null,
                'action_type' => 'clear',
                'updated_by' => $updatedBy,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return $resultJson->setData(['success' => true]);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function handleGetSelectedAttrs()
    {
        $resultJson = $this->resultJsonFactory->create();
        $sourceCode = $this->getRequest()->getParam('source');
        $formatType = $this->getRequest()->getParam('formatType');

        if (!$sourceCode) {
            return $resultJson->setData([
                'success' => false,
                'message' => 'Source code required'
            ]);
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandinventory_selected_attr');

            // Build query based on source and format
            if ($sourceCode === 'KF' && !empty($formatType)) {
                // KF with format: match exact source + format_type
                $record = $connection->fetchRow(
                    "SELECT chosen_attributes FROM {$table} 
                    WHERE source = ? AND format_type = ?
                    ORDER BY id DESC LIMIT 1",
                    [$sourceCode, $formatType]
                );
            } else {
                // Non-KF or KF without format: match source only
                $record = $connection->fetchRow(
                    "SELECT chosen_attributes FROM {$table} 
                    WHERE source = ?
                    ORDER BY id DESC LIMIT 1",
                    [$sourceCode]
                );
            }

            if ($record && $record['chosen_attributes']) {
                $attributes = json_decode($record['chosen_attributes'], true);
                return $resultJson->setData([
                    'success' => true,
                    'attributes' => $attributes
                ]);
            }

            return $resultJson->setData([
                'success' => false,
                'message' => 'No previous selection found'
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getUnmatchedAction()
    {
        $resultJson = $this->resultJsonFactory->create();
        $batchId = $this->getRequest()->getParam('batch_id');

        if (!$batchId) {
            return $resultJson->setData(['success' => false, 'message' => 'Batch ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandinventory_temp');

            $data = $connection->fetchAll(
                "SELECT entity_id, potential_sku, size, color 
                FROM {$table} 
                WHERE batch_id = ? AND (linked_sku IS NULL OR linked_sku = '')
                ORDER BY entity_id",
                [$batchId]
            );

            return $resultJson->setData([
                'success' => true,
                'total' => count($data),
                'items' => $data
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getBatchLogs()
    {
        $resultJson = $this->resultJsonFactory->create();
        $batchId = $this->getRequest()->getParam('batch_id');

        if (!$batchId) {
            return $resultJson->setData(['success' => false, 'message' => 'Batch ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $manualMatchLogTable = $this->resource->getTableName('artm_brandinventory_manual_match_log');

            $comments = $this->batchHistoryService->getComments($batchId);
            $summaryLogs = $connection->fetchAll(
                "SELECT file_path, applied_count, skipped_count, not_found_count, already_linked_count, uploaded_by, created_at
                 FROM {$manualMatchLogTable}
                 WHERE batch_id = ?
                 ORDER BY created_at DESC",
                [$batchId]
            );

            // Convert created_at to Indian timezone
            $indianTimezone = new \DateTimeZone('Asia/Kolkata');
            foreach ($summaryLogs as &$log) {
                if (!empty($log['created_at'])) {
                    $utcDate = new \DateTime($log['created_at'], new \DateTimeZone('UTC'));
                    $utcDate->setTimezone($indianTimezone);
                    // Format: 25-May-2026 03:40 PM (change format as needed)
                    $log['created_at'] = $utcDate->format('d-M-Y h:i A');
                }
            }
            unset($log);

            return $resultJson->setData([
                'success' => true,
                'comments' => $comments,
                'summary_logs' => $summaryLogs
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getMatchSummaryAction()
    {
        $resultJson = $this->resultJsonFactory->create();
        $batchId = $this->getRequest()->getParam('batch_id');

        if (!$batchId) {
            return $resultJson->setData(['success' => false, 'message' => 'Batch ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $tempTable = $this->resource->getTableName('artm_brandinventory_temp');
            $mainTable = $this->resource->getTableName('artm_brand_inventory');

            // Get counts from temp table
            $total = (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM {$tempTable} WHERE batch_id = ?",
                [$batchId]
            );

            $matched = (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM {$tempTable} WHERE batch_id = ? AND linked_sku IS NOT NULL AND linked_sku != ''",
                [$batchId]
            );

            $pending = $total - $matched;

            // Build and overwrite the status JSON
            $statusData = [
                'total'     => $total,
                'matched'   => $matched,
                'pending'   => $pending,
                'synced_at' => date('Y-m-d H:i:s')
            ];

            $connection->update(
                $mainTable,
                [
                    'record_status' => json_encode($statusData)
                ],
                ['id = ?' => $batchId]
            );

            return $resultJson->setData([
                'success' => true,
                'total'   => $total,
                'matched' => $matched,
                'pending' => $pending
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
        /**
     * Update linked_sku in manual match table
     */
    private function handleUpdateManualMatch()
    {
        $resultJson = $this->resultJsonFactory->create();
        $id = (int)$this->getRequest()->getParam('id');
        $linkedSku = trim($this->getRequest()->getParam('linked_sku', ''));

        if (!$id) {
            return $resultJson->setData(['success' => false, 'message' => 'ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandinventory_manual_match');

            $connection->update($table, [
                'linked_sku' => $linkedSku,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id = ?' => $id]);

            return $resultJson->setData(['success' => true, 'message' => 'Updated']);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Delete record from manual match table
     */
    private function handleDeleteManualMatch()
    {
        $resultJson = $this->resultJsonFactory->create();
        $id = (int)$this->getRequest()->getParam('id');

        if (!$id) {
            return $resultJson->setData(['success' => false, 'message' => 'ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandinventory_manual_match');

            $connection->delete($table, ['id = ?' => $id]);

            return $resultJson->setData(['success' => true, 'message' => 'Deleted']);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function getFormats()
    {
        $resultJson = $this->resultJsonFactory->create();
        $sourceCode = $this->getRequest()->getParam('source');

        if (!$sourceCode) {
            return $resultJson->setData([
                'success' => false,
                'message' => 'Source code required'
            ]);
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandInventory_format');

            $select = $connection->select()
                ->from($table, ['id', 'source', 'format', 'format_data'])
                ->where('source = ?', $sourceCode)
                ->order('format ASC');

            $formats = $connection->fetchAll($select);

            $formatted = [];
            foreach ($formats as $fmt) {
                $formatted[] = [
                    'id'          => (int)$fmt['id'],
                    'source'      => $fmt['source'],
                    'format'      => $fmt['format'],
                    'format_data' => $fmt['format_data'] ?? ''
                ];
            }

            return $resultJson->setData([
                'success' => true,
                'formats' => $formatted
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get full format details by ID
     */
    private function getFormatDetails()
    {
        $resultJson = $this->resultJsonFactory->create();
        $formatId = (int)$this->getRequest()->getParam('format_id');

        if (!$formatId) {
            return $resultJson->setData(['success' => false, 'message' => 'Format ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandInventory_format');

            $select = $connection->select()->from($table)->where('id = ?', $formatId);
            $format = $connection->fetchRow($select);

            if (!$format) {
                return $resultJson->setData(['success' => false, 'message' => 'Format not found']);
            }

            $formatData = $format['format_data'] ?? '';
            $parsedData = null;

            if (!empty($formatData)) {
                $decoded = json_decode($formatData, true);

                if (is_array($decoded) && !empty($decoded)) {
                    // ✅ Reverse array so last row is first
                    $reversedRows = array_reverse($decoded);

                    $parsedData = [];
                    foreach ($reversedRows as $row) {
                        if (is_array($row)) {
                            // ✅ Reverse key order: last key becomes first
                            $keys = array_keys($row);
                            $reversedKeys = array_reverse($keys);

                            $newRow = [];
                            foreach ($reversedKeys as $key) {
                                $newRow[$key] = $row[$key];
                            }
                            $parsedData[] = $newRow;
                        } else {
                            $parsedData[] = $row;
                        }
                    }
                } else {
                    $parsedData = $decoded;
                }
            }

            return $resultJson->setData([
                'success' => true,
                'format' => [
                    'id'          => (int)$format['id'],
                    'source'      => $format['source'],
                    'format'      => $format['format'],
                    'heading'     => $format['heading'] ?? 'Choose Sku Containing Column',
                    'format_data' => $parsedData ?? $formatData
                ]
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}