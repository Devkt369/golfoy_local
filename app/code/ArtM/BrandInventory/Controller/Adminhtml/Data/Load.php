<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;
use ArtM\BrandInventory\Helper\Data as BrandInventoryHelper;
use ArtM\BrandInventory\Model\Service\BatchHistoryService;

class Load extends Action
{
    protected $resultJsonFactory;
    protected $resource;
    protected $brandInventoryFactory;
    protected $helper;
    protected $batchHistoryService;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resource,
        \ArtM\BrandInventory\Model\BrandInventoryFactory $brandInventoryFactory,
        BrandInventoryHelper $helper,
        BatchHistoryService $batchHistoryService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        $this->brandInventoryFactory = $brandInventoryFactory;
        $this->helper = $helper;
        $this->batchHistoryService = $batchHistoryService;
    }

    public function execute()
    {

        $result = $this->resultJsonFactory->create();

        try {
            $batchId  = $this->getRequest()->getParam('id');
            $fileName = $this->getRequest()->getParam('file');

            $page     = (int)$this->getRequest()->getParam('page', 1);
            $pageSize = (int)$this->getRequest()->getParam('pageSize', 50);
            $offset   = ($page - 1) * $pageSize;

            $model = $this->brandInventoryFactory->create()->load($batchId);

            if (!$model->getId()) {
                throw new \Exception("Batch not found");
            }

            $selectedFilter = $model->getData('selected_filter');
            $selectSource   = $model->getData('source_code');
            $selectedCols   = json_decode($selectedFilter, true);

            $sheetColumnsJson = $model->getData('sheet_column') ?: '[]';
            $sheetColumns = json_decode($sheetColumnsJson, true) ?: [];

            $rowFiltersJson = $model->getData('row_filters') ?: '[]';
            $rowFilters = json_decode($rowFiltersJson, true) ?: [];

            $connection = $this->resource->getConnection();
            $sourceAttrTable = $this->resource->getTableName('artm_brandinventory_selected_attr');

            $sourceType = $connection->fetchOne(
                "SELECT source_type FROM {$sourceAttrTable} WHERE source = ? LIMIT 1",
                [$selectSource]
            );

            if (!$sourceType) {
                $sourceType = 'style_composite';
            }

            $isDirectSku = ($sourceType === 'direct_sku');
            $table = $this->resource->getTableName('artm_brandinventory_temp');
            
            $existingBatch = $connection->fetchOne(
                "SELECT COUNT(*) FROM {$table} WHERE batch_id = ?",
                [$batchId]
            );

            if ($existingBatch == 0) {
                $filePath = BP . '/var/import/Brand_sheets/brand_stock/' . $fileName;

                if (!file_exists($filePath)) {
                    throw new \Exception("File not found: " . $fileName);
                }

                if (($handle = fopen($filePath, "r")) !== false) {
                    $headers = fgetcsv($handle, 1000, ",");
                    if (!$headers) {
                        throw new \Exception("Invalid CSV header");
                    }

                    $headers = array_map(function($header) {
                        return trim(str_replace("\xEF\xBB\xBF", '', $header));
                    }, $headers);

                    $headers      = array_map('trim', $headers);
                    $headersLower = array_map('strtolower', $headers);
                    $headerCount  = count($headers);

                    if (empty($sheetColumns)) {
                        $sheetColumns = $headers;
                        $connection->update(
                            $this->resource->getTableName('artm_brand_inventory'),
                            ['sheet_column' => json_encode($headers)],
                            ['id = ?' => $batchId]
                        );
                    }

                    $headerIndexMap = [];
                    foreach ($headers as $idx => $h) {
                        $headerIndexMap[strtolower($h)] = $idx;
                    }

                    $shouldSkipRow = function($row) use ($rowFilters, $headerIndexMap) {
                        if (empty($rowFilters)) return false;
                        
                        foreach ($rowFilters as $filter) {
                            $colName = strtolower($filter['columnName'] ?? '');
                            $colIndex = $headerIndexMap[$colName] ?? null;
                            
                            if ($colIndex === null) continue;
                            
                            $cellValue = strtolower(trim($row[$colIndex] ?? ''));
                            
                            foreach ($filter['values'] as $val) {
                                $val = strtolower(trim($val));
                                if ($cellValue === $val || strpos($cellValue, $val) !== false) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    };

                    if ($isDirectSku) {

                        // ═══════════════════════════════════════════════════════
                        // DETECT WHICH DIRECT SKU FLOW TO USE
                        // ═══════════════════════════════════════════════════════

                        // 1. Check if dedicated SKU column exists
                        $skuIndex = $this->findColumnIndex($headers, [
                            'sku', 'SKU', 'Item SKU', 'Product SKU', 'item_code', 'Item Code',
                            'itemcode', 'ItemCode', 'ITEMCODE', 'product_code', 'Product Code',
                            'style', 'Style', 'STYLE', 'style_no', 'Style No','Part Code','Part_Code'
                        ]);

                        // 2. Check if ITEMS DESCRIPTION exists (for SKU extraction)
                        $itemsDescIndex = $this->findColumnIndex($headers, [
                            'items description', 'item description', 'description', 
                            'item desc', 'product description', 'desc', 'details', 'items description'
                        ]);

                        // 3. Determine flow
                        $hasDedicatedSkuColumn = ($skuIndex !== false);
                        $hasItemsDescription = ($itemsDescIndex !== false);

                        if (!$hasDedicatedSkuColumn && $hasItemsDescription) {
                            //echo'has discription'; die();
                            // ═════════════════════════════════════════════════════
                            // NEW FLOW: SKU inside ITEMS DESCRIPTION (BAGBOY, etc.)
                            // ═════════════════════════════════════════════════════
                            $this->processItemsDescriptionFlow($isDirectSku,$handle, $headers, $headerCount, $shouldSkipRow, $batchId, $table, $connection);

                        } elseif ($hasDedicatedSkuColumn) {
                            // ═════════════════════════════════════════════════════
                            // OLD FLOW: Dedicated SKU column (original working code)
                            // ═════════════════════════════════════════════════════
                            $this->processDirectSkuFlow($isDirectSku,$handle, $headers, $headerCount, $shouldSkipRow, $skuIndex, $batchId, $table, $connection);

                        } else {
                            return $result->setData([
                                'success' => false,
                                'message' => 'Cannot find SKU column or Items Description. Found: ' . implode(', ', $headers)
                            ]);
                        }

                    } else {
                        //echo 'style'; die;
                        // ═══════════════════════════════════════════════════════
                        // STYLE COMPOSITE (unchanged)
                        // ═══════════════════════════════════════════════════════
                        $isNewFormat = (
                            count($selectedCols) === 3 &&
                            in_array('Style', $selectedCols) &&
                            in_array('Color', $selectedCols) &&
                            in_array('Size', $selectedCols)
                        );

                         if ($isNewFormat) {
                            $styleGroupIndex = array_search('style group', $headersLower);
                            $styleIndex      = array_search('style', $headersLower);
                            $colorIndex      = array_search('color', $headersLower);
                            $sizeIndex       = array_search('size', $headersLower);
                            $balanceIndex    = array_search('balance', $headersLower);

                            if ($styleIndex === false || $colorIndex === false || $sizeIndex === false) {
                                throw new \Exception("Required columns (Style, Color, Size) not found");
                            }

                            $skippedCount = 0;
                            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                                if (count($row) !== $headerCount) continue;

                                if ($shouldSkipRow($row)) {
                                    $skippedCount++;
                                    continue;
                                }

                                $styleGroup = ($styleGroupIndex !== false && isset($row[$styleGroupIndex]))
                                    ? trim($row[$styleGroupIndex]) : '';
                                $sku = ($styleIndex !== false && isset($row[$styleIndex]))
                                    ? trim($row[$styleIndex]) : '';
                                $color = ($colorIndex !== false && isset($row[$colorIndex]))
                                    ? trim($row[$colorIndex]) : '';
                                $size = ($sizeIndex !== false && isset($row[$sizeIndex]))
                                    ? trim($row[$sizeIndex]) : '';
                                $qty = ($balanceIndex !== false && isset($row[$balanceIndex]))
                                    ? (float) $row[$balanceIndex] : 0;

                                $sku = str_replace(' ', '', $sku);

                                if (empty($sku) || empty($size) || $qty <= 0) continue;

                                $sizeCode = strtoupper(trim($size));

                                $params = [
                                    ['value' => $sku, 'variation' => false],
                                    ['value' => $color, 'variation' => true],
                                    ['value' => $sizeCode, 'variation' => false],
                                ];

                                $potentialSkus = $this->helper->createPotentialSku($params, 50);
                                $primaryPotentialSku = !empty($potentialSkus) ? $potentialSkus[0] : $sku;

                                $exists = $connection->fetchOne(
                                    "SELECT COUNT(*) FROM {$table} 
                                     WHERE potential_sku = ? AND batch_id = ? AND size = ?",
                                    [$primaryPotentialSku, $batchId, $size]
                                );

                                if ($exists) continue;

                                $updateQty = $this->calculateUpdateQty($qty);
                                $rowData = array_combine($headers, $row);

                                $connection->insert($table, [
                                    'batch_id'      => $batchId,
                                    'product_data'  => json_encode($rowData),
                                    'style_code'    => $styleGroup,
                                    'style'         => $sku,
                                    'size'          => $size,
                                    'qty'           => $qty,
                                    'update_qty'    => $updateQty,
                                    'color'         => $color,
                                    'potential_sku' => json_encode($potentialSkus),
                                    'match_status'  => 'pending'
                                ]);
                            }

                        } else {
                            $skuIndex = array_search('style', $headersLower);
                            $colorIndex = null;

                            foreach ($headers as $i => $h) {
                                if (strpos(strtolower($h), 'color') !== false) {
                                    $colorIndex = $i;
                                    break;
                                }
                            }

                            $skippedCount = 0;
                            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                                if (count($row) !== $headerCount) continue;

                                if ($shouldSkipRow($row)) {
                                    $skippedCount++;
                                    continue;
                                }

                                $rowData = array_combine($headers, $row);
                                if (!$rowData) continue;

                                $sku   = ($skuIndex !== false && isset($row[$skuIndex])) ? trim($row[$skuIndex]) : '';
                                $color = ($colorIndex !== null && isset($row[$colorIndex])) ? trim($row[$colorIndex]) : '';
                                $sku = str_replace(' ', '', $sku);

                                $styleCode = '';
                                foreach ($rowData as $headerKey => $headerValue) {
                                    if (preg_match('/style[\s_-]*group/i', $headerKey)) {
                                        $styleCode = trim($headerValue);
                                        break;
                                    }
                                }

                                foreach ($selectedCols as $colLabel) {
                                    if (!isset($rowData[$colLabel])) continue;

                                    $value = (float) $rowData[$colLabel];
                                    if ($value <= 0) continue;

                                    $size = $colLabel;
                                    $sizeCode = strtoupper(trim($size));

                                    $params = [
                                        ['value' => $sku, 'variation' => false],
                                        ['value' => $color, 'variation' => true],
                                        ['value' => $sizeCode, 'variation' => false],
                                    ];

                                    $potentialSkus = $this->helper->createPotentialSku($params, 50);
                                    $primaryPotentialSku = !empty($potentialSkus) ? $potentialSkus[0] : $sku;

                                    $exists = $connection->fetchOne(
                                        "SELECT COUNT(*) FROM {$table} 
                                         WHERE potential_sku = ? AND batch_id = ? AND size = ?",
                                        [$primaryPotentialSku, $batchId, $size]
                                    );

                                    if ($exists) continue;

                                    $updateQty = $this->calculateUpdateQty($value);

                                    $connection->insert($table, [
                                        'batch_id'      => $batchId,
                                        'product_data'  => json_encode($rowData),
                                        'style_code'    => $styleCode,
                                        'style'         => $sku,
                                        'size'          => $size,
                                        'qty'           => $value,
                                        'update_qty'    => $updateQty,
                                        'color'         => $color,
                                        'potential_sku' => json_encode($potentialSkus),
                                        'match_status'  => 'pending'
                                    ]);
                                }
                            }
                        }
                    }

                    fclose($handle);
                }
            }

            // Pagination & response (unchanged)
            $totalRecords = $connection->fetchOne(
                "SELECT COUNT(*) FROM {$table} WHERE batch_id = ?",
                [$batchId]
            );

            $data = $connection->fetchAll(
                "SELECT entity_id, potential_sku, style_code, style, product_data, linked_sku, 
                        match_status, update_qty, size, qty, color, location 
                 FROM {$table} 
                 WHERE batch_id = ?
                 ORDER BY entity_id DESC
                 LIMIT {$pageSize} OFFSET {$offset}",
                [$batchId]
            );

            foreach ($data as &$row) {
                $decoded = json_decode($row['potential_sku'], true);
                if (is_array($decoded)) {
                    $row['potential_sku'] = implode(', ', $decoded);
                    $row['potential_skus_array'] = $decoded;
                }
            }

            $adminUser = $this->_auth->getUser();
            $loadedBy  = $adminUser ? $adminUser->getUsername() : 'system';
            $this->batchHistoryService->appendComment($batchId, 'loaded', $loadedBy);

            $batchStatus = $connection->fetchOne(
                "SELECT status FROM {$this->resource->getTableName('artm_brand_inventory')} WHERE id = ?",
                [$batchId]
            );

            $syncStatus = (int) ($batchStatus ?? 0);

            return $result->setData([
                'success'        => true,
                'source_type'    => $sourceType,
                'sheet_columns'  => $sheetColumns,
                'message'        => $existingBatch > 0
                    ? 'Loaded from existing batch'
                    : 'CSV processed successfully',
                'data'           => $data,
                'sync_status'    => $syncStatus,
                'pagination'     => [
                    'total_records' => (int) $totalRecords,
                    'current_page'  => $page,
                    'page_size'     => $pageSize,
                    'total_pages'   => ceil($totalRecords / $pageSize)
                ]
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════
    // OLD FLOW: Dedicated SKU column (ORIGINAL WORKING CODE)
    // ═══════════════════════════════════════════════════════
    private function processDirectSkuFlow($isDirectSku,$handle, array $headers, int $headerCount, callable $shouldSkipRow, $skuIndex, int $batchId, string $table, $connection)
    {
        $qtyIndex = $this->findColumnIndex($headers, [
            'qty', 'Qty', 'quantity', 'Quantity', 'Order Qty', 'order_qty', 
            'Stock', 'stock', 'Balance', 'balance', 'O/s', 'o/s'
        ]);

        $productTypeIndex = $this->findColumnIndex($headers, [
            'product_type', 'Product Type', 'category', 'Category', 'model', 'Model'
        ]);

        $skippedCount = 0;
        rewind($handle);
        fgetcsv($handle); // Skip header

        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            if (count($row) !== $headerCount) continue;

            if ($shouldSkipRow($row)) {
                $skippedCount++;
                continue;
            }

            $sku = isset($row[$skuIndex]) ? trim($row[$skuIndex]) : '';
            $qty = $qtyIndex !== false && isset($row[$qtyIndex]) 
                ? (float) str_replace(',', '', $row[$qtyIndex]) : 0;
            $product_type = $productTypeIndex !== false && isset($row[$productTypeIndex]) 
                ? trim($row[$productTypeIndex]) : '';

            if (empty($sku)) continue;
            if ($qty <= 0) continue;

            $rowData = array_combine($headers, $row);
            if (!$rowData) continue;

            $potentialSkus = [$sku];

            $exists = $connection->fetchOne(
                "SELECT COUNT(*) FROM {$table} 
                 WHERE potential_sku = ? AND batch_id = ?",
                [json_encode($potentialSkus), $batchId]
            );

            if ($exists) continue;
            //$updateQty = $this->calculateUpdateQty($qty);
            $updateQty = $isDirectSku ? $qty : $this->calculateUpdateQty($qty);
            $connection->insert($table, [
                'batch_id'      => $batchId,
                'product_data'  => json_encode($rowData),
                'style_code'    => $product_type,
                'style'         => $sku,
                'size'          => '',
                'qty'           => $qty,
                'update_qty'    => $updateQty,
                'color'         => '',
                'potential_sku' => json_encode($potentialSkus),
                'match_status'  => 'pending',
                'location'      => null
            ]);
        }

        if ($skippedCount > 0) {
            $connection->update(
                $this->resource->getTableName('artm_brand_inventory'),
                ['skipped_count' => $skippedCount],
                ['id = ?' => $batchId]
            );
        }
    }

    // ═══════════════════════════════════════════════════════
    // NEW FLOW: SKU inside ITEMS DESCRIPTION (BAGBOY style)
    // ═══════════════════════════════════════════════════════
    private function processItemsDescriptionFlow($isDirectSku, $handle, array $headers, int $headerCount, callable $shouldSkipRow, int $batchId, string $table, $connection)
    {
        // Read all rows into array
        $csvRows = [];
        $skippedCount = 0;
        
        rewind($handle);
        fgetcsv($handle); // Skip header

        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            if (count($row) !== $headerCount) continue;
            if ($shouldSkipRow($row)) { $skippedCount++; continue; }

            $rowData = array_combine($headers, $row);
            if (!$rowData) continue;

            $csvRows[] = $rowData;
        }

        // Find column names
        $itemsDescCol = $this->findColumn($headers, [
            'items description', 'item description', 'description', 
            'item desc', 'product description', 'desc', 'details', 'items description'
        ]);
        
        $locationCol = $this->findColumn($headers, [
            'location', 'Location', 'LOCATION', 'warehouse', 'Warehouse', 
            'branch', 'Branch', 'store', 'Store', 'city', 'City'
        ]);

        // Process with grouping by extracted SKU
        $processed = $this->formatSkufromDiscription($csvRows, $itemsDescCol, $locationCol);

        // Save to database
        foreach ($processed as $item) {
            //print_r($item); die;
            $sku = $item['Sku']; // Already extracted by formatSkufromDiscription
            $potentialSkus = [$sku];

            // Check duplicate by SKU + batch
            $exists = $connection->fetchOne(
                "SELECT COUNT(*) FROM {$table} WHERE style = ? AND batch_id = ?",
                [$sku, $batchId]
            );
            if ($exists) continue;

            $connection->insert($table, [
                'batch_id'      => $batchId,
                'product_data'  => json_encode($item),
                'style_code'    => $item['MODEL'] ?? ($item['CATEGORY'] ?? ''),
                'style'         => $sku,
                'size'          => '',
                'qty'           => isset($item['LIMIT VALUE']) ? (float) str_replace(',', '', $item['LIMIT VALUE']) : 0,
                'update_qty'    => isset($item['Order Qty']) ? (float) str_replace(',', '', $item['Order Qty']) : 0,
                'color'         => '',
                'potential_sku' => json_encode($potentialSkus),
                'match_status'  => 'pending',
                'location'      => json_encode($item['LOCATIONS'] ?? [])
            ]);
        }

        if ($skippedCount > 0) {
            $connection->update(
                $this->resource->getTableName('artm_brand_inventory'),
                ['skipped_count' => $skippedCount],
                ['id = ?' => $batchId]
            );
        }
    }

    // ═══════════════════════════════════════════════════════
    // YOUR HELPER FUNCTIONS
    // ═══════════════════════════════════════════════════════

    private function sortSku($sku)
    {
        $sku = trim(str_replace("\xEF\xBB\xBF", '', $sku));
        $parts = preg_split('/[\s\(]+/', $sku, 2);
        return $parts[0];
    }

    private function formatSkufromDiscription(array $rows, string $skuColumn, ?string $locationColumn)
    {
        $result = [];

        foreach ($rows as $row) {
            $sku = $this->sortSku($row[$skuColumn]);

            // Create product only once per unique SKU
            if (!isset($result[$sku])) {
                $result[$sku] = $row;

                // Create location container
                $result[$sku]['LOCATIONS'] = [];

                // Remove old location column from main row
                if ($locationColumn && isset($result[$sku][$locationColumn])) {
                    unset($result[$sku][$locationColumn]);
                }

                // Store extracted SKU
                $result[$sku]['Sku'] = $sku;
            }

            // Add location details
            $location = $locationColumn ? ($row[$locationColumn] ?? 'UNKNOWN') : 'UNKNOWN';

            $result[$sku]['LOCATIONS'][$location] = [
                'LIMIT VALUE'  => isset($row['LIMIT VALUE']) ? (float) str_replace(',', '', $row['LIMIT VALUE']) : 0,
                'AVAILABILITY' => $row['AVAILABILITY'] ?? ''
            ];
        }

        return array_values($result);
    }

    private function findColumn(array $headers, array $possibleNames)
    {
        $headersLower = array_map('strtolower', $headers);
        
        foreach ($possibleNames as $name) {
            $index = array_search(strtolower($name), $headersLower);
            if ($index !== false) {
                return $headers[$index];
            }
        }
        
        foreach ($possibleNames as $name) {
            $nameLower = strtolower($name);
            foreach ($headersLower as $idx => $header) {
                if (strpos($header, $nameLower) !== false) {
                    return $headers[$idx];
                }
            }
        }
        
        return null;
    }

    private function findColumnIndex(array $headers, array $possibleNames)
    {
        $headersLower = array_map('strtolower', $headers);
        
        foreach ($possibleNames as $name) {
            $index = array_search(strtolower($name), $headersLower);
            if ($index !== false) return $index;
        }
        
        foreach ($possibleNames as $name) {
            $nameLower = strtolower($name);
            foreach ($headersLower as $idx => $header) {
                if (strpos($header, $nameLower) !== false) return $idx;
            }
        }
        
        return false;
    }

    private function calculateUpdateQty($qty)
    {
        $qty = (int)$qty;
        if ($qty <= 0) return 0;
        if ($qty < 5) return 0;
        if ($qty == 5) return 1;
        return (int)(floor(($qty - 6) / 3) + 2);
    }

    protected function _isAllowed()
    {
        return true;
    }
}