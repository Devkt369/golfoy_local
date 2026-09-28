<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\App\ResourceConnection;

class Download extends Action
{
    protected $fileFactory;
    protected $fileDriver;
    protected $resource;

    public function __construct(
        Action\Context $context,
        FileFactory $fileFactory,
        FileDriver $fileDriver,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->fileDriver  = $fileDriver;
        $this->resource    = $resource;
    }

    public function execute()
    {
        $fileName = $this->getRequest()->getParam('file');
        $batchId  = $this->getRequest()->getParam('batch_id');
        $type     = $this->getRequest()->getParam('type', 'original');
        $reportsDir = BP . '/var/import/Brand_sheets/reports';

        // ✅ REPORT: serve from batch table ONLY, generate fresh if missing
        if ($batchId && $type === 'report') {
            $connection = $this->resource->getConnection();
            $savedFile = $connection->fetchOne(
                "SELECT report_file FROM {$this->resource->getTableName('artm_brand_inventory')} WHERE id = ?",
                [$batchId]
            );

           // print_r($savedFile); die;

            // If batch table has a report file, serve it
            if ($savedFile) {
                $filePath = $reportsDir . '/' . basename($savedFile);
                if ($this->fileDriver->isExists($filePath)) {
                    return $this->fileFactory->create(
                        basename($savedFile),
                        [
                            'type'  => 'filename',
                            'value' => $filePath,
                            'rm'    => false
                        ],
                        \Magento\Framework\App\Filesystem\DirectoryList::ROOT
                    );
                }
            }
            
            $fileName = $this->generateReport($batchId);
            if (!$fileName) {
                $this->messageManager->addErrorMessage(__('No report available for this batch.'));
                //return $this->_redirect('*/*/');
                return $this->_redirect($this->_redirect->getRefererUrl());
            }

            // Save the newly generated filename to the batch table
            $connection->update(
                $this->resource->getTableName('artm_brand_inventory'),
                ['report_file' => $fileName],
                ['id = ?' => $batchId]
            );

            $filePath = $reportsDir . '/' . $fileName;
            return $this->fileFactory->create(
                $fileName,
                [
                    'type'  => 'filename',
                    'value' => $filePath,
                    'rm'    => false
                ],
                \Magento\Framework\App\Filesystem\DirectoryList::ROOT
            );
        }

        // ✅ MANUAL MATCH REPORT
        if ($batchId && $type === 'report_manual') {
            $fileName = $this->generateManualReport($batchId);
            if (!$fileName) {
                $this->messageManager->addErrorMessage(__('Failed to generate manual match report.'));
                return $this->_redirect('*/*/');
            }
            $filePath = $reportsDir . '/' . basename($fileName);
            if (!$this->fileDriver->isExists($filePath)) {
                $this->messageManager->addErrorMessage(__('File not found.'));
                return $this->_redirect('*/*/');
            }
            return $this->fileFactory->create(
                basename($fileName),
                [
                    'type'  => 'filename',
                    'value' => $filePath,
                    'rm'    => false
                ],
                \Magento\Framework\App\Filesystem\DirectoryList::ROOT
            );
        }

        if (!$fileName) {
            return $this->_redirect('*/*/');
        }

        // Determine base path and file path based on type
        if ($type === 'report') {
            $basePath = BP . '/var/import/Brand_sheets/reports';
            $filePath = $basePath . '/' . basename($fileName);
        } elseif ($type === 'report_manual') {
            $basePath = BP . '/var/import/Brand_sheets/reports';
            $filePath = $basePath . '/' . basename($fileName);
        } elseif ($type === 'manual_match') {
            $relPath = trim($fileName, "\\/");
            $relPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relPath);
            $filePath = BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . $relPath;

            if (!$this->fileDriver->isExists($filePath)) {
                $altPath = BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . str_replace(['\\', DIRECTORY_SEPARATOR], '/', $relPath);
                if ($this->fileDriver->isExists($altPath)) {
                    $filePath = $altPath;
                }
            }
        } else {
            $basePath = BP . '/var/import/Brand_sheets/brand_stock';
            $filePath = $basePath . '/' . basename($fileName);
        }

        if (!$this->fileDriver->isExists($filePath)) {
            $this->messageManager->addErrorMessage(__('File not found.'));
            return $this->_redirect('*/*/');
        }

        return $this->fileFactory->create(
            basename($fileName),
            [
                'type'  => 'filename',
                'value' => $filePath,
                'rm'    => false
            ],
            \Magento\Framework\App\Filesystem\DirectoryList::ROOT
        );
    }

    /**
     * Generate report CSV and save to server
     */
    // private function generateReport($batchId)
    // {
    //     try {
    //         $connection = $this->resource->getConnection();
    //         $tempTable = $this->resource->getTableName('artm_brandinventory_temp');
    //         $reportsDir = BP . '/var/import/Brand_sheets/reports';

    //         // 1. DELETE old report files for this batch
    //         $pattern = $reportsDir . '/batch_report_' . $batchId . '_*.csv';
    //         $oldFiles = glob($pattern);
    //         if (!empty($oldFiles)) {
    //             foreach ($oldFiles as $oldFile) {
    //                 if (is_file($oldFile)) {
    //                     @unlink($oldFile);
    //                 }
    //             }
    //         }

    //         // 2. FETCH fresh data from temp table
    //         $rows = $connection->fetchAll(
    //             "SELECT entity_id, product_data, size, style, qty, style_code, update_qty, color, 
    //                     potential_sku, linked_sku, match_status, inventory_sync, created_at 
    //             FROM {$tempTable} 
    //             WHERE batch_id = ? 
    //             ORDER BY entity_id DESC",
    //             [$batchId]
    //         );

    //         if (empty($rows)) {
    //             return false;
    //         }

    //         $csvContent = "ID,Style Group,Brand SKU,Gender,Size,Brand Qty,Update Qty,Color,Potential SKU,Linked Magento SKU,Match Status,Inventory Synced,Created At\n";

    //         foreach ($rows as $row) {
    //             //echo '<pre>'; print_r($row); die;
    //             $styleGroup = $row['style_code'] ?: '-';
    //             $brandSku   = $row['style'] ?: '-';
    //             $gender     = '-';

    //             if ($row['product_data']) {
    //                 try {
    //                     $pd = json_decode($row['product_data'], true);
                        
    //                     if ($styleGroup === '-' || $styleGroup === '') {
    //                         $styleGroup = $pd['StyleGroup'] 
    //                             ?? $pd['style_group'] 
    //                             ?? $pd['Style Group'] 
    //                             ?? '-';
    //                     }
                        
    //                     if ($brandSku === '-' || $brandSku === '') {
    //                         $brandSku = $pd['style'] ?? $pd['Style'] ?? '-';
    //                     }
                        
    //                     $gender = $pd['gender'] ?? $pd['Gender'] ?? '-';
                        
    //                 } catch (\Exception $e) {}
    //             }

    //             $potentialSkus = $row['potential_sku'] ?? '-';
    //             try {
    //                 $decoded = json_decode($potentialSkus, true);
    //                 if (is_array($decoded)) {
    //                     $potentialSkus = implode(' | ', $decoded);
    //                 }
    //             } catch (\Exception $e) {}

    //             $escapeCsv = function($str) {
    //                 $str = str_replace('"', '""', (string)$str);
    //                 return '"' . $str . '"';
    //             };

    //             $csvContent .= implode(',', [
    //                 $escapeCsv($row['entity_id']),
    //                 $escapeCsv($styleGroup),
    //                 $escapeCsv($brandSku),
    //                 $escapeCsv($gender),
    //                 $escapeCsv($row['size'] ?? '-'),
    //                 $escapeCsv($row['qty'] ?? '0'),
    //                 $escapeCsv($row['update_qty'] ?? '0'),
    //                 $escapeCsv($row['color'] ?? '-'),
    //                 $escapeCsv($potentialSkus),
    //                 $escapeCsv($row['linked_sku'] ?? ''),
    //                 $escapeCsv($row['match_status'] ?? 'pending'),
    //                 $escapeCsv($row['inventory_sync'] ? 'Yes' : 'No'),
    //                 $escapeCsv($row['created_at'] ?? '')
    //             ]) . "\n";
    //         }

    //         // 3. SAVE fresh report
    //         if (!is_dir($reportsDir)) {
    //             mkdir($reportsDir, 0777, true);
    //         }

    //         $fileName = 'batch_report_' . $batchId . '_' . date('Ymd_His') . '.csv';
    //         $filePath = $reportsDir . '/' . $fileName;
    //         file_put_contents($filePath, $csvContent);

    //         return $fileName;

    //     } catch (\Exception $e) {
    //         return false;
    //     }
    // }

    private function generateReport($batchId)
{
    try {
        $connection = $this->resource->getConnection();
        $tempTable = $this->resource->getTableName('artm_brandinventory_temp');
        $reportsDir = BP . '/var/import/Brand_sheets/reports';

        // 1. DELETE old report files for this batch
        $pattern = $reportsDir . '/batch_report_' . $batchId . '_*.csv';
        $oldFiles = glob($pattern);
        if (!empty($oldFiles)) {
            foreach ($oldFiles as $oldFile) {
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
        }

        // 2. FETCH fresh data from temp table
        $rows = $connection->fetchAll(
            "SELECT entity_id, product_data, size, style, qty, style_code, update_qty, color, 
                    potential_sku, linked_sku, match_status, inventory_sync, created_at 
            FROM {$tempTable} 
            WHERE batch_id = ? 
            ORDER BY entity_id DESC",
            [$batchId]
        );

        if (empty($rows)) {
            return false;
        }

        // 3. DYNAMICALLY DETECT WHICH COLUMNS HAVE DATA
        $hasStyleGroup = false;
        $hasBrandSku = false;
        $hasGender = false;
        $hasSize = false;
        $hasQty = false;
        $hasUpdateQty = false;
        $hasColor = false;
        $hasPotentialSku = false;
        $hasLinkedSku = false;
        $hasMatchStatus = false;
        $hasInventorySync = false;
        $hasCreatedAt = false;

        // Also track dynamic columns from product_data
        $dynamicColumns = [];

        foreach ($rows as $row) {
            if (!empty($row['style_code'])) $hasStyleGroup = true;
            if (!empty($row['style'])) $hasBrandSku = true;
            if (!empty($row['size'])) $hasSize = true;
            if (!empty($row['qty']) && $row['qty'] != 0) $hasQty = true;
            if (!empty($row['update_qty']) && $row['update_qty'] != 0) $hasUpdateQty = true;
            if (!empty($row['color'])) $hasColor = true;
            if (!empty($row['potential_sku'])) $hasPotentialSku = true;
            if (!empty($row['linked_sku'])) $hasLinkedSku = true;
            if (!empty($row['match_status'])) $hasMatchStatus = true;
            if (!empty($row['inventory_sync'])) $hasInventorySync = true;
            if (!empty($row['created_at'])) $hasCreatedAt = true;

            // Check product_data for gender and dynamic fields
            if ($row['product_data']) {
                try {
                    $pd = json_decode($row['product_data'], true);
                    if (!empty($pd)) {
                        // Check gender
                        if (!empty($pd['gender']) || !empty($pd['Gender'])) {
                            $hasGender = true;
                        }
                        // Collect all unique keys from product_data as dynamic columns
                        foreach (array_keys($pd) as $key) {
                            $lowerKey = strtolower(str_replace(' ', '', $key));
                            // Skip keys that we already handle explicitly
                            if (!in_array($lowerKey, ['stylegroup', 'style_group', 'style', 'gender', 'color', 'size', 'qty', 'quantity'])) {
                                if (!isset($dynamicColumns[$lowerKey])) {
                                    $dynamicColumns[$lowerKey] = $key; // Store original key name
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {}
            }
        }

        // 4. BUILD CSV HEADER DYNAMICALLY
        $headers = ['ID'];
        if ($hasStyleGroup) $headers[] = 'Style Group';
        if ($hasBrandSku) $headers[] = 'Brand SKU';
        if ($hasGender) $headers[] = 'Gender';
        
        // Add dynamic columns from product_data
        foreach ($dynamicColumns as $originalKey) {
            $headers[] = $originalKey;
        }
        
        if ($hasSize) $headers[] = 'Size';
        if ($hasQty) $headers[] = 'Brand Qty';
        if ($hasUpdateQty) $headers[] = 'Update Qty';
        if ($hasColor) $headers[] = 'Color';
        if ($hasPotentialSku) $headers[] = 'Potential SKU';
        if ($hasLinkedSku) $headers[] = 'Linked Magento SKU';
        if ($hasMatchStatus) $headers[] = 'Match Status';
        if ($hasInventorySync) $headers[] = 'Inventory Synced';
        if ($hasCreatedAt) $headers[] = 'Created At';

        $csvContent = implode(',', array_map(function($h) {
            return '"' . str_replace('"', '""', $h) . '"';
        }, $headers)) . "\n";

        // 5. BUILD CSV ROWS
        foreach ($rows as $row) {
            $styleGroup = $row['style_code'] ?: '-';
            $brandSku   = $row['style'] ?: '-';
            $gender     = '-';

            // Parse product_data
            $pd = [];
            if ($row['product_data']) {
                try {
                    $pd = json_decode($row['product_data'], true);
                    
                    if (($styleGroup === '-' || $styleGroup === '') && !empty($pd)) {
                        $styleGroup = $pd['StyleGroup'] 
                            ?? $pd['style_group'] 
                            ?? $pd['Style Group'] 
                            ?? '-';
                    }
                    
                    if (($brandSku === '-' || $brandSku === '') && !empty($pd)) {
                        $brandSku = $pd['style'] ?? $pd['Style'] ?? '-';
                    }
                    
                    $gender = $pd['gender'] ?? $pd['Gender'] ?? '-';
                    
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

            // Build row data matching headers
            $rowData = [$escapeCsv($row['entity_id'])];
            
            if ($hasStyleGroup) $rowData[] = $escapeCsv($styleGroup);
            if ($hasBrandSku) $rowData[] = $escapeCsv($brandSku);
            if ($hasGender) $rowData[] = $escapeCsv($gender);
            
            // Add dynamic column values
            foreach ($dynamicColumns as $lowerKey => $originalKey) {
                $value = $pd[$originalKey] ?? $pd[$lowerKey] ?? '-';
                $rowData[] = $escapeCsv($value);
            }
            
            if ($hasSize) $rowData[] = $escapeCsv($row['size'] ?? '-');
            if ($hasQty) $rowData[] = $escapeCsv($row['qty'] ?? '0');
            if ($hasUpdateQty) $rowData[] = $escapeCsv($row['update_qty'] ?? '0');
            if ($hasColor) $rowData[] = $escapeCsv($row['color'] ?? '-');
            if ($hasPotentialSku) $rowData[] = $escapeCsv($potentialSkus);
            if ($hasLinkedSku) $rowData[] = $escapeCsv($row['linked_sku'] ?? '');
            if ($hasMatchStatus) $rowData[] = $escapeCsv($row['match_status'] ?? 'pending');
            if ($hasInventorySync) $rowData[] = $escapeCsv($row['inventory_sync'] ? 'Yes' : 'No');
            if ($hasCreatedAt) $rowData[] = $escapeCsv($row['created_at'] ?? '');

            $csvContent .= implode(',', $rowData) . "\n";
        }

        // 6. SAVE fresh report
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $fileName = 'batch_report_' . $batchId . '_' . date('Ymd_His') . '.csv';
        $filePath = $reportsDir . '/' . $fileName;
        file_put_contents($filePath, $csvContent);

        return $fileName;

    } catch (\Exception $e) {
        return false;
    }
}

    /**
     * Generate manual match report CSV and save to server
     */
    private function generateManualReport($batchId)
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandinventory_manual_match');

            $rows = $connection->fetchAll(
                "SELECT id, linked_sku, brand_created_sku, source_code, status, created_date FROM {$table} WHERE batch_id = ? ORDER BY id DESC",
                [$batchId]
            );

            if (empty($rows)) {
                return false;
            }

            $csvContent = "ID,Brand SKU,Style Group,Gender,Size,Color,Linked SKU,Status,Source,Created At\n";

            foreach ($rows as $row) {
                $bcs = [];
                if (!empty($row['brand_created_sku'])) {
                    try {
                        $decoded = json_decode($row['brand_created_sku'], true);
                        $bcs = is_array($decoded) ? $decoded : [];
                    } catch (\Exception $e) {}
                }

                $brandSku = $bcs['brand_sku'] ?? ($bcs['style_group'] ?? '-');
                $styleGroup = $bcs['style_group'] ?? '-';
                $gender = $bcs['gender'] ?? '-';
                $size = $bcs['size'] ?? '-';
                $color = $bcs['color'] ?? '-';

                $escapeCsv = function($str) {
                    $str = str_replace('"', '""', (string)$str);
                    return '"' . $str . '"';
                };

                $csvContent .= implode(',', [
                    $escapeCsv($row['id'] ?? ''),
                    $escapeCsv($brandSku),
                    $escapeCsv($styleGroup),
                    $escapeCsv($gender),
                    $escapeCsv($size),
                    $escapeCsv($color),
                    $escapeCsv($row['linked_sku'] ?? ''),
                    $escapeCsv($row['status'] ?? ''),
                    $escapeCsv($row['source_code'] ?? ''),
                    $escapeCsv($row['created_date'] ?? '')
                ]) . "\n";
            }

            $reportsDir = BP . '/var/import/Brand_sheets/reports';
            if (!is_dir($reportsDir)) {
                mkdir($reportsDir, 0777, true);
            }

            $fileName = 'manual_match_report_' . $batchId . '_' . date('Ymd_His') . '.csv';
            $filePath = $reportsDir . '/' . $fileName;
            file_put_contents($filePath, $csvContent);

            return $fileName;

        } catch (\Exception $e) {
            return false;
        }
    }

    protected function _isAllowed()
    {
        return true;
    }
}