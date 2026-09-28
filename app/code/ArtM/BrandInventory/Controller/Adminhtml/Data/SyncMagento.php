<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use ArtM\BrandInventory\Model\Service\BatchHistoryService;
use ArtM\BrandInventory\Model\Service\ManualMatchService;

class SyncMagento extends Action
{
    protected $resultJsonFactory;
    protected $resource;
    protected $productCollectionFactory;
    protected $productRepository;
    protected $batchHistoryService;
    protected $manualMatchService;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resource,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepository $productRepository,
        BatchHistoryService $batchHistoryService,
        ManualMatchService $manualMatchService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->batchHistoryService = $batchHistoryService;
        $this->manualMatchService = $manualMatchService;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $action = $this->getRequest()->getParam('action');
        if ($action === 'syncBatch') {
            return $this->handleSyncBatch();
        }

        return $resultJson->setData(['success' => false, 'message' => 'Invalid action']);
    }

    /**
     * Sync entire batch — tries all potential SKUs per item with color verification
     */
    public function handleSyncBatch()
    {
        $resultJson = $this->resultJsonFactory->create();
        $batchId = $this->getRequest()->getParam('batch_id');

        if (!$batchId) {
            return $resultJson->setData(['success' => false, 'message' => 'Batch ID required']);
        }

        try {
            $connection = $this->resource->getConnection();
            $tempTable = $this->resource->getTableName('artm_brandinventory_temp');

            // Get source_type for this batch
            $sourceType = $this->getBatchSourceType($batchId);

            // =========================================================
            // 🆕 GET ALL RECORDS FOR DUPLICATE CHECK (not just unmatched)
            // =========================================================
            $allBatchItems = $connection->fetchAll(
                "SELECT entity_id, potential_sku, linked_sku, match_status 
                FROM {$tempTable} 
                WHERE batch_id = ?",
                [$batchId]
            );

            // Build lookup of already-used potential_skus → linked_sku mappings
            $batchPotentialSkuMap = [];
            foreach ($allBatchItems as $batchItem) {
                $skus = $this->decodePotentialSkus($batchItem['potential_sku'] ?? '');
                foreach ($skus as $sku) {
                    $sku = strtoupper(trim($sku));
                    if (empty($sku)) continue;
                    
                    // Track: potential_sku → [entity_id, linked_sku, match_status]
                    if (!isset($batchPotentialSkuMap[$sku])) {
                        $batchPotentialSkuMap[$sku] = [];
                    }
                    $batchPotentialSkuMap[$sku][] = [
                        'entity_id' => $batchItem['entity_id'],
                        'linked_sku' => $batchItem['linked_sku'],
                        'match_status' => $batchItem['match_status']
                    ];
                }
            }

            // =========================================================
            // GET ONLY UNMATCHED ITEMS TO PROCESS
            // =========================================================
            $items = $connection->fetchAll(
                "SELECT entity_id, potential_sku, color, size, style 
                FROM {$tempTable} 
                WHERE batch_id = ? 
                AND (linked_sku IS NULL OR linked_sku = '')",
                [$batchId]
            );

            $matched = 0;
            $failed = 0;
            $manualMatched = 0;
            $duplicates = 0;
            
            $usedMagentoSkus = [];
            $potentialSkuGroups = [];

            foreach ($items as $item) {
                $entityId = $item['entity_id'];
                
                // =========================================================
                // 🆕 ENHANCED DUPLICATE CHECK: Check against ALL batch records
                // =========================================================
                $currentPotentialSkus = $this->decodePotentialSkus($item['potential_sku'] ?? '');
                $isDuplicateInBatch = false;
                $duplicateLinkedSku = null;

                foreach ($currentPotentialSkus as $currentSku) {
                    $currentSku = strtoupper(trim($currentSku));
                    if (empty($currentSku)) continue;

                    // Check if this potential_sku already exists in another record in this batch
                    if (isset($batchPotentialSkuMap[$currentSku])) {
                        foreach ($batchPotentialSkuMap[$currentSku] as $existing) {
                            // Skip self-check
                            if ($existing['entity_id'] == $entityId) continue;
                            
                            // If another record with same potential_sku already has a linked_sku, mark as duplicate
                            if (!empty($existing['linked_sku'])) {
                                $isDuplicateInBatch = true;
                                $duplicateLinkedSku = $existing['linked_sku'];
                                break 2; // Break both loops
                            }
                        }
                    }
                }

                // If duplicate found in batch, mark and skip
                if ($isDuplicateInBatch) {
                    $connection->update(
                        $tempTable,
                        [
                            'linked_sku' => null,
                            'match_status' => 'duplicate_record_found'
                        ],
                        ['entity_id = ?' => $entityId]
                    );
                    $duplicates++;
                    continue;
                }

                // =========================================================
                // ORIGINAL DUPLICATE CHECK: Same potential_skus within this batch (unmatched items)
                // =========================================================
                $skus = $this->decodePotentialSkus($item['potential_sku'] ?? '');
                foreach ($skus as &$s) { $s = strtoupper(trim($s)); }
                sort($skus);
                $key = implode(',', $skus);
                
                if (!isset($potentialSkuGroups[$key])) {
                    $potentialSkuGroups[$key] = $entityId;
                }

                if ($potentialSkuGroups[$key] != $entityId) {
                    $connection->update(
                        $tempTable,
                        [
                            'linked_sku' => null,
                            'match_status' => 'duplicate_record_found'
                        ],
                        ['entity_id = ?' => $entityId]
                    );
                    $duplicates++;
                    continue;
                }

                $csvColor = strtoupper(trim($item['color'] ?? ''));
                $csvSize = strtoupper(trim($item['size'] ?? ''));
                $potentialSkus = $this->decodePotentialSkus($item['potential_sku'] ?? '');
                
                $foundSku = null;
                $foundMatchResult = null;
                $matchSource = null;
                $manualMatch = null;

                // =========================================================
                // BRANCH BY SOURCE TYPE
                // =========================================================
                if ($sourceType === 'direct_sku') {
                    $brandSku = strtoupper(trim($item['style'] ?? ''));
                    //echo $brandSku; die('brandSku');
                    // =========================================================
                    // STEP 1: CHECK MANUAL MATCH TABLE
                    // =========================================================
                    
                    $connection = $this->resource->getConnection();
                    $manualTable = $this->resource->getTableName('artm_brandinventory_manual_match');
                    
                    // For direct_sku, search by linked_sku = potential_sku directly
                    // This is the most reliable since combined_sku might be empty
                    if (!empty($potentialSkus)) {
                        $upperPotentialSkus = array_map(function($s) { 
                            return strtoupper(trim($s)); 
                        }, $potentialSkus);
                        
                        $placeholders = implode(',', array_fill(0, count($upperPotentialSkus), '?'));
                        
                        $manualMatch = $connection->fetchRow(
                            "SELECT linked_sku, source_code, status, created_date, brand_created_sku 
                            FROM {$manualTable} 
                            WHERE linked_sku IN ({$placeholders})
                            ORDER BY created_date DESC 
                            LIMIT 1",
                            $upperPotentialSkus
                        );
                    }
                    
                    // Fallback: search by brand_sku or combined_sku in JSON if linked_sku didn't match
                    if (!$manualMatch && !empty($brandSku)) {
                        $manualMatch = $connection->fetchRow(
                            "SELECT linked_sku, source_code, status, created_date, brand_created_sku 
                            FROM {$manualTable} 
                            WHERE JSON_UNQUOTE(JSON_EXTRACT(brand_created_sku, '$.brand_sku')) = ?
                            OR JSON_UNQUOTE(JSON_EXTRACT(brand_created_sku, '$.combined_sku')) = ?
                            OR JSON_UNQUOTE(JSON_EXTRACT(brand_created_sku, '$.style_group')) = ?
                            ORDER BY created_date DESC 
                            LIMIT 1",
                            [$brandSku, $brandSku, $brandSku]
                        );
                    }
                    
                    // Another fallback: try potential_sku as combined_sku
                    if (!$manualMatch && !empty($potentialSkus)) {
                        //echo'<pre>';print_r($potentialSkus); die('potentialSkus'); // Debug log
                        foreach ($potentialSkus as $ps) {
                            //$ps = strtoupper(trim($ps));
                            $ps = strtoupper(preg_replace('/\s+/', '', trim($ps)));
                            if (empty($ps)) continue;
                            //echo $ps; die('ps'); // Debug log
                            
                            $manualMatch = $connection->fetchRow(
                                "SELECT linked_sku, source_code, status, created_date, brand_created_sku 
                                FROM {$manualTable} 
                                WHERE JSON_UNQUOTE(JSON_EXTRACT(brand_created_sku, '$.combined_sku')) = ?
                                ORDER BY created_date DESC 
                                LIMIT 1",
                                [$ps]
                            );
                            if ($manualMatch) break;
                        }
                    }
                    
                    if ($manualMatch && !empty($manualMatch['linked_sku'])) {
                        $manualLinkedSku = strtoupper(trim($manualMatch['linked_sku']));
                        if (!isset($usedMagentoSkus[$manualLinkedSku])) {
                            $foundSku = $manualLinkedSku;
                            $foundMatchResult = ['sku' => $manualLinkedSku];
                            $matchSource = 'manual';
                        }
                    }

                    // STEP 2: AUTO-MATCH if no manual match
                    if (!$foundSku) {
                        foreach ($potentialSkus as $potentialSku) {
                            $potentialSku = trim($potentialSku);
                            if (empty($potentialSku)) continue;

                            $matchSku = strtoupper($potentialSku);
                            
                            if (isset($usedMagentoSkus[$matchSku])) continue;

                            try {
                                $product = $this->productRepository->get($potentialSku);
                                
                                if ($product && $product->getId()) {
                                    $foundSku = $potentialSku;
                                    $foundMatchResult = ['sku' => $potentialSku];
                                    $matchSource = 'auto';
                                    break;
                                }
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }

                } else {

                    // ORIGINAL SHEET: Style-Color-Size composite matching
                    $brandSku = strtoupper(trim($item['style'] ?? ''));
                    $combinedSku = preg_replace('/\s+/', '', $brandSku . '-' . $csvColor . '-' . $csvSize);

                    // STEP 1: CHECK MANUAL MATCH TABLE
                    $manualMatch = $this->getManualMatch($combinedSku, $potentialSkus, $brandSku, $csvColor, $csvSize);
                    
                    if ($manualMatch && !empty($manualMatch['linked_sku'])) {
                        $manualLinkedSku = strtoupper(trim($manualMatch['linked_sku']));

                        if (!isset($usedMagentoSkus[$manualLinkedSku])) {
                            $foundSku = $manualLinkedSku;
                            $foundMatchResult = ['sku' => $manualLinkedSku];
                            $matchSource = 'manual';
                        }
                    }

                    // STEP 2: AUTO-MATCH if no manual match
                    if (!$foundSku) {
                        foreach ($potentialSkus as $potentialSku) {
                            $potentialSku = trim($potentialSku);
                            if (empty($potentialSku)) continue;

                            $matchResult = $this->findExactMagentoSku($potentialSku);
                            
                            if (!$matchResult) continue;

                            $matchSku = strtoupper(trim($matchResult['sku']));
                            
                            if (isset($usedMagentoSkus[$matchSku])) continue;

                            try {
                                $product = $this->productRepository->get($potentialSku);
                                
                                if (!$product || !$product->getId()) continue;

                                $magentoColor = $this->getProductColor($product);
                                
                                if (!$this->colorsMatch($csvColor, $magentoColor)) continue;

                                $foundSku = $potentialSku;
                                $foundMatchResult = $matchResult;
                                $matchSource = 'auto';
                                break;

                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }
                }

                // =========================================================
                // STEP 3: SAVE RESULTS (shared logic for both types)
                // =========================================================
                if ($foundMatchResult && $foundSku) {

                //echo'<pre>';print_r($foundMatchResult); die(' final found'); // Debug log
                    $magentoSku = strtoupper(trim($foundMatchResult['sku']));
                    
                    if (isset($usedMagentoSkus[$magentoSku])) {
                        $connection->update(
                            $tempTable,
                            [
                                'linked_sku' => null,
                                'match_status' => 'duplicate_record_found'
                            ],
                            ['entity_id = ?' => $entityId]
                        );
                        $duplicates++;
                    } else {
                        $usedMagentoSkus[$magentoSku] = $entityId;

                        $status = $matchSource === 'manual' ? 'table_matched' : 'sync_matched';

                        $connection->update(
                            $tempTable,
                            [
                                'linked_sku' => $magentoSku,
                                'match_status' => $status
                            ],
                            ['entity_id = ?' => $entityId]
                        );
                        $matched++;
                        
                        if ($matchSource === 'manual') {
                            $manualMatched++;
                        }

                        // Cache auto-match for future uploads
                        if ($matchSource === 'auto') {
                            $tempRowData = $this->manualMatchService->getTempRowData($entityId);
                            if ($tempRowData) {
                                $sourceCode = $this->manualMatchService->getBatchSourceCode($batchId);
                                $brandCreateData = [
                                    'style_group' => $tempRowData['style_code'] ?? null,
                                    'brand_sku'   => $tempRowData['brand_sku'] ?? null,
                                    'gender'      => $tempRowData['gender'] ?? null,
                                    'color'       => $tempRowData['color'] ?? null,
                                    'size'        => $tempRowData['size'] ?? null
                                ];
                                
                                $this->manualMatchService->saveManualMatch(
                                    $batchId,
                                    $entityId,
                                    $magentoSku,
                                    $sourceCode,
                                    'auto_matched',
                                    $brandCreateData
                                );
                            }
                        }
                    }
                } else {
                    // Determine if failure was due to duplicate or truly not found
                    $isDuplicate = false;
                    
                    // If manual match found but blocked by usedMagentoSkus
                    if ($manualMatch && !empty($manualMatch['linked_sku'])) {
                        $manualLinkedSku = strtoupper(trim($manualMatch['linked_sku']));
                        if (isset($usedMagentoSkus[$manualLinkedSku])) {
                            $isDuplicate = true;
                        }
                    }

                    if ($isDuplicate) {
                        $connection->update(
                            $tempTable,
                            [
                                'linked_sku' => null,
                                'match_status' => 'duplicate_record_found'
                            ],
                            ['entity_id = ?' => $entityId]
                        );
                        $duplicates++;
                    } else {
                        $connection->update(
                            $tempTable,
                            [
                                'linked_sku' => null,
                                'match_status' => 'not_found'
                            ],
                            ['entity_id = ?' => $entityId]
                        );
                        $failed++;
                    }
                }
            }

            $resultData = [
                'success' => true,
                'message' => "Sync complete",
                'matched' => $matched,
                'manual_matched' => $manualMatched,
                'auto_matched' => $matched - $manualMatched,
                'duplicates' => $duplicates,
                'failed' => $failed,
                'total' => count($items)
            ];

            // Update status Magento sku matched
            $batchTable = $this->resource->getTableName('artm_brand_inventory');
            $update = ['status' => 1];
            $connection->update($batchTable, $update, ['id = ?' => $batchId]);

            $adminUser = $this->_auth->getUser();
            $syncedBy = $adminUser ? $adminUser->getUsername() : 'system';
            $this->batchHistoryService->appendComment($batchId, 'synced', $syncedBy);

            return $resultJson->setData($resultData);

        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    /**
     * Get source_type for a batch from selected_attr table
     */
    public function getBatchSourceType($batchId)
    {
        try {

            $connection = $this->resource->getConnection();
            
            // Step 1: Get source_code from main inventory table
            $inventoryTable = $this->resource->getTableName('artm_brand_inventory');
            $sourceCode = $connection->fetchOne(
                "SELECT source_code FROM {$inventoryTable} WHERE id = ? LIMIT 1",
                [$batchId]
            );
            
            
            if (!$sourceCode) {
                return 'style_composite';
            }
            
            // Step 2: Match source_code with 'source' column in selected_attr to get source_type
            $selectedAttrTable = $this->resource->getTableName('artm_brandinventory_selected_attr');
            $sourceType = $connection->fetchOne(
                "SELECT source_type FROM {$selectedAttrTable} WHERE source = ? LIMIT 1",
                [$sourceCode]
            );
             
            return $sourceType ?: 'style_composite';
            
        } catch (\Exception $e) {
            return 'style_composite';
        }
    }
    /**
     * Lookup manual match table by brand_created_sku
     */
    public function getManualMatch($brandCreatedSku, array $potentialSkus = [])
    {

        $connection = $this->resource->getConnection();
        $manualTable = $this->resource->getTableName('artm_brandinventory_manual_match');

        // Strategy 1: Search by combined_sku (style-color-size)
        $select = $connection->select()
            ->from($manualTable, ['linked_sku', 'source_code', 'status', 'created_date', 'brand_created_sku'])
            ->where("JSON_UNQUOTE(JSON_EXTRACT(brand_created_sku, '$.combined_sku')) = ?", $brandCreatedSku)
            ->order('created_date DESC')
            ->limit(1);

        $result = $connection->fetchRow($select);

        //echo'<pre>';print_r($result); die;
        if ($result && !empty($result['linked_sku'])) {
            return $result;
        }

        // Strategy 2: Fallback — search by any potential_sku in the linked_sku column
        // This catches cases where combined_sku was built differently
        if (!empty($potentialSkus)) {
            foreach ($potentialSkus as $potentialSku) {
                $potentialSku = strtoupper(trim($potentialSku));
                if (empty($potentialSku)) continue;

                $select2 = $connection->select()
                    ->from($manualTable, ['linked_sku', 'source_code', 'status', 'created_date', 'brand_created_sku'])
                    ->where('linked_sku = ?', $potentialSku)
                    ->order('created_date DESC')
                    ->limit(1);

                $result2 = $connection->fetchRow($select2);
                if ($result2 && !empty($result2['linked_sku'])) {
                    return $result2;
                }
            }
        }

        return false;
    }

/**
 * Save a successful auto-match to the manual table so next upload matches instantly.
 * Uses current batch_id and marks source_code as auto-sync.
 */
    private function saveManualMatch($entityId, $linkedSku, $batchId)

    {
        try {
            $connection = $this->resource->getConnection();
            $manualMatchTable = $this->resource->getTableName('artm_brandinventory_manual_match');

            // ✅ SAME AS Updates.php: Get full temp row data via service
            $tempRowData = $this->manualMatchService->getTempRowData($entityId);
            if (!$tempRowData) {
                return;
            }

            //print_r($tempRowData); die;

            // ✅ SAME AS Updates.php: Get source_code from batch table
            $sourceCode = $this->manualMatchService->getBatchSourceCode($batchId);
            // ✅ SAME JSON STRUCTURE as Updates.php recordManualMatch()
            $brandCreateData = [
                'style_group' => $tempRowData['style_code'] ?? null,
                'brand_sku' => $tempRowData['brand_sku'] ?? null,
                'combined_sku' => ($tempRowData['brand_sku'] ?? '') . '-' . ($tempRowData['color'] ?? '') . '-' . ($tempRowData['size'] ?? ''),
                'gender' => $tempRowData['gender'] ?? null,
                'color' => $tempRowData['color'] ?? null,
                'size' => $tempRowData['size'] ?? null
            ];

            // 🆕 Duplicate check: prevent saving same brand_sku + linked_sku combo
           $existing = $connection->fetchOne(
                "SELECT id FROM {$manualMatchTable} 
                WHERE JSON_UNQUOTE(JSON_EXTRACT(brand_created_sku, '$.brand_sku')) = ? 
                AND linked_sku = ? 
                AND status = 'auto_matched'",
                [$brandCreateData['brand_sku'], $linkedSku]
            );

            if ($existing) {
                return; // Already cached, skip
            }

            $connection->insert($manualMatchTable, [
                'batch_id' => $batchId,
                'linked_sku' => $linkedSku,
                'brand_created_sku' => json_encode($brandCreateData, JSON_UNESCAPED_UNICODE),
                'source_code' => $sourceCode,
                'status' => 'auto_matched',
                'created_date' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            // Silently fail — don't break the batch sync
        }
    }

    /**
     * Decode potential_skus from JSON or string
     */
    private function decodePotentialSkus($rawPotential)
    {
        $potentialSkus = [];
        try {
            if (strpos(trim($rawPotential), '[') === 0) {
                $decoded = json_decode($rawPotential, true);
                $potentialSkus = is_array($decoded) ? $decoded : [$rawPotential];
            } else {
                $potentialSkus = [$rawPotential];
            }
        } catch (\Exception $e) {
            $potentialSkus = [$rawPotential];
        }
        return $potentialSkus;
    }

    /**
     * Get color attribute from Magento product
     */
    private function getProductColor($product)
    {
        $colorAttributes = ['color', 'product_color', 'item_color', 'shade', 'colour'];
        
        foreach ($colorAttributes as $attrCode) {
            $color = $product->getData($attrCode);
            if ($color) {
                if (is_numeric($color)) {
                    $attr = $product->getResource()->getAttribute($attrCode);
                    if ($attr && $attr->usesSource()) {
                        $color = $attr->getSource()->getOptionText($color);
                    }
                }
                if ($color) return strtoupper(trim($color));
            }
        }
        return '';
    }

    /**
     * Compare CSV color with Magento color
     */
    private function colorsMatch($csvColor, $magentoColor)
    {
        $csvColor = strtoupper(trim($csvColor));
        $magentoColor = strtoupper(trim($magentoColor));

        if (empty($csvColor) || empty($magentoColor)) {
            return false;
        }

        // Normalize common spelling variations
        $normalize = function($str) {
            $str = str_replace('GREY', 'GRAY', $str);
            $str = str_replace('COLOUR', 'COLOR', $str);
            return $str;
        };

        $nCsv = $normalize($csvColor);
        $nMag = $normalize($magentoColor);

        // 1. Exact match (normalized)
        if ($nCsv === $nMag) return true;

        // 2. Containment either direction (normalized)
        if (strpos($nCsv, $nMag) !== false || strpos($nMag, $nCsv) !== false) return true;

        // 3. Word-by-word cross check (normalized)
        $csvWords = array_filter(preg_split('/[\s\-]+/', $nCsv));
        $magWords = array_filter(preg_split('/[\s\-]+/', $nMag));

        foreach ($csvWords as $word) {
            if (strlen($word) >= 2 && strpos($nMag, $word) !== false) return true;
        }
        foreach ($magWords as $word) {
            if (strlen($word) >= 2 && strpos($nCsv, $word) !== false) return true;
        }

        // 4. Common word (normalized)
        if (!empty(array_intersect($csvWords, $magWords))) return true;

        return false;
    }

    private function normalizeColor($str)
    {
        return strtoupper(trim(preg_replace('/[\s\-_]+/', ' ', $str)));
    }
    
    private function tokensMatch($a, $b)
    {
        if ($a === $b) return true;

        $lenA = strlen($a);
        $lenB = strlen($b);

        if ($lenA === $lenB) return false;

        $short = $lenA < $lenB ? $a : $b;
        $long  = $lenA < $lenB ? $b : $a;

        // Guardrails to prevent false positives
        if (strlen($short) < 2) return false;
        if (strlen($short) / strlen($long) > 0.8) return false;
        if ($short[0] !== $long[0]) return false;

        return $this->isSubsequence($short, $long);
    }

/**
 * Check if $needle is a subsequence of $haystack (all chars appear in order)
 */
    private function isSubsequence($needle, $haystack)
    {
        $nLen = strlen($needle);
        $hLen = strlen($haystack);
        $n = 0;
        $h = 0;

        while ($n < $nLen && $h < $hLen) {
            if ($needle[$n] === $haystack[$h]) {
                $n++;
            }
            $h++;
        }

        return $n === $nLen;
    }

    /**
     * Find exact Magento SKU match
     */
    public function findExactMagentoSku($potentialSku)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['sku', 'entity_id', 'color']);
        $collection->addAttributeToFilter('sku', ['eq' => $potentialSku]);
        $collection->setPageSize(1);

        $product = $collection->getFirstItem();
        if ($product->getId()) {
            return ['sku' => $product->getSku(), 'product_id' => $product->getId()];
        }
        return null;
    }
}