<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ProductRepository;
use ArtM\BrandInventory\Model\Service\BatchHistoryService;
use ArtM\BrandInventory\Model\Service\ManualMatchService;

class ManualMatch extends Action
{
    protected $resultJsonFactory;
    protected $resource;
    protected $filesystem;
    protected $productRepository;
    protected $batchHistoryService;
    protected $manualMatchService;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resource,
        Filesystem $filesystem,
        ProductRepository $productRepository,
        BatchHistoryService $batchHistoryService,
        ManualMatchService $manualMatchService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        $this->filesystem = $filesystem;
        $this->productRepository = $productRepository;
        $this->batchHistoryService = $batchHistoryService;
        $this->manualMatchService = $manualMatchService;
    }

    public function execute()
    {
        $sourceType ='';
        $resultJson = $this->resultJsonFactory->create();

        $batchId = $this->getRequest()->getParam('batch_id');
        $file = $this->getRequest()->getFiles('match_file');

        if (!$batchId) {
            return $resultJson->setData(['success' => false, 'message' => 'Batch ID required']);
        }

        if (!$file || !isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return $resultJson->setData(['success' => false, 'message' => 'Please upload a CSV file']);
        }

        try {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                return $resultJson->setData(['success' => false, 'message' => 'Cannot read file']);
            }

            // ═══════════════════════════════════════════════════════
            // GET SOURCE TYPE FOR THIS BATCH
            // ═══════════════════════════════════════════════════════
            $sourceType = $this->getSourceTypeByBatchId($batchId);
            $isDirectSku = ($sourceType === 'direct_sku');
            //echo $sourceType; die;

            $headers = fgetcsv($handle, 0, ',');
            if (!$headers || count($headers) < 2) {
                fclose($handle);
                return $resultJson->setData(['success' => false, 'message' => 'CSV must have at least 2 columns']);
            }

            // Normalize headers
            $headers = array_map(function($h) {
                return strtolower(trim(str_replace([' ', '_', '-'], '', $h)));
            }, $headers);

            // ═══════════════════════════════════════════════════════
            // DYNAMIC COLUMN MAPPING BASED ON SOURCE TYPE
            // ═══════════════════════════════════════════════════════
            $colMap = [];
            
            if ($isDirectSku) {
                // DIRECT SKU FORMAT: SKU, Product Type, Potential SKU, Magento SKU
                $requiredCols = [
                    'sku'           => ['sku', 'itemsku', 'productsku', 'itemcode'],
                    'producttype'   => ['producttype', 'type', 'category'],
                    'potentialsku'  => ['potentialsku', 'potential_sku'],
                    'magentosku'    => ['magentosku', 'magento_sku', 'linkedsku']
                ];

                foreach ($requiredCols as $key => $possibleNames) {
                    $idx = $this->findColumnIndex($headers, $possibleNames);
                    if ($idx === false && $key !== 'producttype') {
                        // producttype is optional for direct_sku
                        fclose($handle);
                        return $resultJson->setData([
                            'success' => false,
                            'message' => "Missing required column for Direct SKU: {$key}. Expected one of: " . implode(', ', $possibleNames)
                        ]);
                    }
                    $colMap[$key] = $idx;
                }

            } else {
                // STYLE COMPOSITE FORMAT: Style Group, Style, Color, Size, Potential SKU, Magento SKU
                $requiredCols = [
                    'stylegroup'    => ['stylegroup', 'style_group'],
                    'style'         => ['style', 'styles', 'brandsku'],
                    'color'         => ['color', 'colors'],
                    'size'          => ['size', 'sizes'],
                    'potentialsku'  => ['potentialsku', 'potential_sku'],
                    'magentosku'    => ['magentosku', 'magento_sku', 'linkedsku']
                ];

                foreach ($requiredCols as $key => $possibleNames) {
                    $idx = $this->findColumnIndex($headers, $possibleNames);
                    if ($idx === false) {
                        fclose($handle);
                        return $resultJson->setData([
                            'success' => false,
                            'message' => "Missing required column: {$key}. Expected one of: " . implode(', ', $possibleNames)
                        ]);
                    }
                    $colMap[$key] = $idx;
                }
            }

            $savedFilePath = $this->saveUploadedFile($file, $batchId);

            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandinventory_temp');

            $applied = 0;
            $skipped = 0;
            $notFound = 0;
            $alreadyLinked = 0;
            $details = [];

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (count($row) < max(array_filter($colMap, function($v) { return $v !== false; })) + 1) {
                    continue;
                }

                // ═══════════════════════════════════════════════════════
                // EXTRACT VALUES BASED ON SOURCE TYPE
                // ═══════════════════════════════════════════════════════
                if ($isDirectSku) {
                    $sku = str_replace(' ', '', trim($row[$colMap['sku']] ?? ''));
                    $productType = $colMap['producttype'] !== false 
                        ? trim($row[$colMap['producttype']] ?? '') 
                        : '';
                    $potentialSkuInput = trim($row[$colMap['potentialsku']] ?? '');
                    $magentoSku = strtoupper(trim($row[$colMap['magentosku']] ?? ''));

                    // For direct_sku: potential sku = sku itself
                    if (empty($potentialSkuInput)) {
                        $potentialSkuInput = $sku;
                    }

                    $styleGroup = '';
                    $style = $sku;
                    $color = '';
                    $size = '';

                } else {
                    $styleGroup = trim($row[$colMap['stylegroup']] ?? '');
                    $style = str_replace(' ', '', trim($row[$colMap['style']] ?? ''));
                    $color = trim($row[$colMap['color']] ?? '');
                    $size = trim($row[$colMap['size']] ?? '');
                    $potentialSkuInput = trim($row[$colMap['potentialsku']] ?? '');
                    $magentoSku = strtoupper(trim($row[$colMap['magentosku']] ?? ''));
                }

                if (empty($magentoSku)) {
                    $skipped++;
                    $details[] = [
                        'potential_sku' => $potentialSkuInput,
                        'magento_sku' => '',
                        'status' => 'skipped',
                        'reason' => 'Empty Magento SKU'
                    ];
                    continue;
                }

                // ═══════════════════════════════════════════════════════
                // FIND MATCHING RECORD IN DATABASE
                // ═══════════════════════════════════════════════════════
                $dbRow = null;

                if ($isDirectSku) {
                    $originalSku = trim($row[$colMap['sku']] ?? '');
                    $productType = $colMap['producttype'] !== false 
                        ? trim($row[$colMap['producttype']] ?? '') 
                        : '';
                    $potentialSkuInput = trim($row[$colMap['potentialsku']] ?? '');
                    $magentoSku = strtoupper(trim($row[$colMap['magentosku']] ?? ''));

                    if (empty($potentialSkuInput)) {
                        $potentialSkuInput = $originalSku;
                    }

                    $styleGroup = '';
                    $style = $originalSku;  // Use original, not space-stripped
                    $color = '';
                    $size = '';

                    // ═══════════════════════════════════════════════════════
                    // MATCHING: Try multiple strategies
                    // ═══════════════════════════════════════════════════════
                    
                    // Strategy 1: Match on style (exact, with spaces)
                    $dbRow = $connection->fetchRow(
                        "SELECT entity_id, potential_sku, linked_sku 
                        FROM {$table} 
                        WHERE batch_id = ? AND style = ?",
                        [$batchId, $originalSku]
                    );

                    // Strategy 2: Match on style with spaces stripped
                    if (!$dbRow) {
                        $noSpaceSku = str_replace(' ', '', $originalSku);
                        $dbRow = $connection->fetchRow(
                            "SELECT entity_id, potential_sku, linked_sku 
                            FROM {$table} 
                            WHERE batch_id = ? AND REPLACE(style, ' ', '') = ?",
                            [$batchId, $noSpaceSku]
                        );
                    }

                    // Strategy 3: Match on potential_sku using JSON_UNQUOTE
                    if (!$dbRow && !empty($potentialSkuInput)) {
                        $dbRow = $connection->fetchRow(
                            "SELECT entity_id, potential_sku, linked_sku 
                            FROM {$table} 
                            WHERE batch_id = ? 
                            AND JSON_UNQUOTE(potential_sku) LIKE ?",
                            [$batchId, '%' . $potentialSkuInput . '%']
                        );
                    }

                    // Strategy 4: Match on potential_sku using JSON_SEARCH
                    if (!$dbRow && !empty($potentialSkuInput)) {
                        $dbRow = $connection->fetchRow(
                            "SELECT entity_id, potential_sku, linked_sku 
                            FROM {$table} 
                            WHERE batch_id = ? 
                            AND JSON_SEARCH(potential_sku, 'one', ?) IS NOT NULL",
                            [$batchId, $potentialSkuInput]
                        );
                    }
                } else {
                    // For style_composite: match by style + color + size
                    $dbRow = $connection->fetchRow(
                        "SELECT entity_id, potential_sku, linked_sku 
                        FROM {$table} 
                        WHERE batch_id = ? 
                        AND style = ? 
                        AND color = ? 
                        AND size = ?",
                        [$batchId, $style, $color, $size]
                    );

                    // Fallback: search by potential_sku
                    if (!$dbRow && !empty($potentialSkuInput)) {
                        $dbRow = $connection->fetchRow(
                            "SELECT entity_id, potential_sku, linked_sku 
                            FROM {$table} 
                            WHERE batch_id = ? 
                            AND potential_sku LIKE ?",
                            [$batchId, '%' . $potentialSkuInput . '%']
                        );
                    }
                }

                if (!$dbRow) {
                    $notFound++;
                    $details[] = [
                        'potential_sku' => $potentialSkuInput,
                        'magento_sku' => $magentoSku,
                        'status' => 'not_found',
                        'reason' => 'No matching record found in batch'
                    ];
                    continue;
                }

                // Check if Magento SKU already linked to another row
                $existingLinked = $connection->fetchOne(
                    "SELECT entity_id FROM {$table} 
                    WHERE batch_id = ? 
                    AND linked_sku = ? 
                    AND entity_id != ?",
                    [$batchId, $magentoSku, $dbRow['entity_id']]
                );

                if ($existingLinked) {
                    $alreadyLinked++;
                    $details[] = [
                        'potential_sku' => $potentialSkuInput,
                        'magento_sku' => $magentoSku,
                        'status' => 'skipped',
                        'reason' => 'Magento SKU already linked to another record'
                    ];
                    continue;
                }

                // Skip if already linked to same SKU
                if ($dbRow['linked_sku'] && strtoupper(trim($dbRow['linked_sku'])) === $magentoSku) {
                    $skipped++;
                    $details[] = [
                        'potential_sku' => $potentialSkuInput,
                        'magento_sku' => $magentoSku,
                        'status' => 'skipped',
                        'reason' => 'Already linked to same SKU'
                    ];
                    continue;
                }

                // Save the link
                $connection->update(
                    $table,
                    [
                        'linked_sku' => $magentoSku,
                        'match_status' => 'updated_via_sheet'
                    ],
                    ['entity_id = ?' => $dbRow['entity_id']]
                );

                // Save to manual match table for future batches
                $tempRowData = $this->manualMatchService->getTempRowData($dbRow['entity_id']);
                if ($tempRowData) {
                    $sourceCode = $this->manualMatchService->getBatchSourceCode($batchId);
                    
                    if ($isDirectSku) {
                        $brandCreateData = [
                            'sku' => $tempRowData['style'] ?? null,
                            'product_type' => $productType ?: ($tempRowData['style_code'] ?? null),
                        ];
                    } else {
                        $brandCreateData = [
                            'style_group' => $tempRowData['style_code'] ?? null,
                            'brand_sku' => $tempRowData['brand_sku'] ?? null,
                            'gender' => $tempRowData['gender'] ?? null,
                            'color' => $tempRowData['color'] ?? null,
                            'size' => $tempRowData['size'] ?? null
                        ];
                    }
                    
                    $this->manualMatchService->saveManualMatch(
                        $batchId,
                        $dbRow['entity_id'],
                        $magentoSku,
                        $sourceCode,
                        'bulk_matched',
                        $sourceType,
                        $brandCreateData
                    );
                }

                $applied++;
                $details[] = [
                    'potential_sku' => $potentialSkuInput,
                    'magento_sku' => $magentoSku,
                    'temp_id' => $dbRow['entity_id'],
                    'old_linked_sku' => $dbRow['linked_sku'],
                    'status' => 'applied',
                    'reason' => 'Linked successfully'
                ];
            }

            fclose($handle);

            $this->logUpload($batchId, $savedFilePath, $applied, $skipped, $notFound, $alreadyLinked, $details);

            if ($applied > 0) {
                $adminUser = $this->_auth->getUser();
                $uploadedBy = $adminUser ? $adminUser->getUsername() : 'system';
                $details_meta = [
                    'file' => basename($savedFilePath),
                    'file_path' => $savedFilePath,
                    'applied_count' => $applied,
                    'total_processed' => ($applied + $skipped + $notFound + $alreadyLinked)
                ];
                $this->batchHistoryService->appendComment($batchId, 'sku_updated_bulk', $uploadedBy, $details_meta);
            }

            return $resultJson->setData([
                'success' => true,
                'message' => 'Manual matching complete',
                'source_type' => $sourceType,
                'stats' => [
                    'applied' => $applied,
                    'skipped' => $skipped,
                    'not_found' => $notFound,
                    'already_linked' => $alreadyLinked
                ],
                'details' => $details,
                'saved_file' => $savedFilePath
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get source_type for a given batch ID
     */
    private function getSourceTypeByBatchId($batchId)
    {
        if (empty($batchId)) {
            return 'style_composite'; // default fallback
        }

        $connection = $this->resource->getConnection();
        $brandInventoryTable = $this->resource->getTableName('artm_brand_inventory');
        
        $sourceCode = $connection->fetchOne(
            "SELECT source_code FROM {$brandInventoryTable} WHERE id = ? LIMIT 1",
            [$batchId]
        );

        if (!$sourceCode) {
            return 'style_composite'; // default fallback
        }

        $attrTable = $this->resource->getTableName('artm_brandinventory_selected_attr');
        
        $sourceType = $connection->fetchOne(
            "SELECT source_type FROM {$attrTable} WHERE source = ? LIMIT 1",
            [$sourceCode]
        );

        return $sourceType ?: 'style_composite';
    }

    /**
     * Find column index with flexible header matching
     */
    private function findColumnIndex($headers, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            $idx = array_search($name, $headers);
            if ($idx !== false) {
                return $idx;
            }
        }
        return false;
    }

    /**
     * Save uploaded file to var/import/Brand_sheets/manual_matches_sku/
     */
    private function saveUploadedFile($file, $batchId)
    {
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $basePath = 'import' . DIRECTORY_SEPARATOR . 'Brand_sheets' . DIRECTORY_SEPARATOR . 'manual_matches_sku' . DIRECTORY_SEPARATOR . 'Batch_' . $batchId;
        $varDir->create($basePath);

        $adminUser = $this->_auth->getUser();
        $userId = $adminUser ? $adminUser->getId() : '0';
        $timestamp = date('Ymd_His');
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = "{$timestamp}_uid{$userId}.{$extension}";
        $filePath = $basePath . DIRECTORY_SEPARATOR . $fileName;

        $tempContent = file_get_contents($file['tmp_name']);
        $varDir->writeFile($filePath, $tempContent);

        return $filePath;
    }

    /**
     * Log upload to database
     */
    private function logUpload($batchId, $filePath, $applied, $skipped, $notFound, $alreadyLinked, $details = [])
    {
        try {
            $connection = $this->resource->getConnection();
            $logTable = $this->resource->getTableName('artm_brandinventory_manual_match_log');
            $detailLogTable = $this->resource->getTableName('artm_brandinventory_log');
            
            $adminUser = $this->_auth->getUser();
            $username = $adminUser ? $adminUser->getUsername() : 'unknown';

            $connection->insert($logTable, [
                'batch_id' => $batchId,
                'file_path' => $filePath,
                'applied_count' => $applied,
                'skipped_count' => $skipped,
                'not_found_count' => $notFound,
                'already_linked_count' => $alreadyLinked,
                'uploaded_by' => $username,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $logData = [];
            foreach ($details as $detail) {
                if ($detail['status'] === 'applied') {
                    $logData[] = [
                        'temp_id' => $detail['temp_id'],
                        'batch_id' => $batchId,
                        'potential_sku' => $detail['potential_sku'],
                        'old_linked_sku' => $detail['old_linked_sku'],
                        'new_linked_sku' => $detail['magento_sku'],
                        'old_update_qty' => null,
                        'new_update_qty' => null,
                        'action_type' => 'updated_via_sheet',
                        'updated_by' => $username,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
            
            if (!empty($logData)) {
                $columns = array_keys($logData[0]);
                $placeholders = [];
                $values = [];
                
                foreach ($logData as $row) {
                    $placeholders[] = '(' . str_repeat('?,', count($columns) - 1) . '?)';
                    foreach ($columns as $column) {
                        $values[] = $row[$column];
                    }
                }
                
                $sql = "INSERT INTO {$detailLogTable} (" . implode(',', $columns) . ") VALUES " . implode(',', $placeholders);
                $connection->query($sql, $values);
            }

        } catch (\Exception $e) {
            // Silently fail logging
        }
    }
}