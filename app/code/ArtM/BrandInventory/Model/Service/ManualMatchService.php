<?php

namespace ArtM\BrandInventory\Model\Service;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class ManualMatchService
{
    protected $resource;
    protected $logger;

    public function __construct(ResourceConnection $resource, LoggerInterface $logger = null)
    {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Save manual match record
     * 
     *  @param int $batchId
     * @param int $tempId
     * @param string $linkedSku
     * @param string $sourceCode
     * @param string $status (manually_matched or bulk_matched)
     * @param array $brandCreateData (style_group, brand_sku, gender, color, size)
     * @return bool
     */
    public function saveManualMatch($batchId, $tempId, $linkedSku, $sourceCode, $status, $sourceType, $brandCreateData = [])
    {
        try {
            $connection = $this->resource->getConnection();
            $manualMatchTable = $this->resource->getTableName('artm_brandinventory_manual_match');

            if (!$connection->isTableExists($manualMatchTable)) {
                $this->log('Manual match table not found: ' . $manualMatchTable);
                return false;
            }

            if ($sourceType === 'direct_sku') {
                // direct_sku uses: [sku => ..., product_type => ...]
                //$brandSku    = preg_replace('/\s+/', '', $brandCreateData['sku'] ?? '');
                $brandSku = strtoupper(preg_replace('/\s+/', '', $brandCreateData['sku'] ?? ''));
                $styleGroup  = $brandCreateData['product_type'] ?? null;
                $combinedSku = $brandSku; // For direct_sku, combined = sku itself
                $color       = null;
                $size        = null;
                $gender      = null;
            } else {
                // style_composite uses: [brand_sku => ..., style_group => ..., color => ..., size => ...]
                $brandSku   = preg_replace('/\s+/', '', $brandCreateData['brand_sku'] ?? '');
                $styleGroup = $brandCreateData['style_group'] ?? null;
                $color      = $brandCreateData['color'] ?? null;
                $size       = strtoupper($brandCreateData['size'] ?? '');
                $gender     = $brandCreateData['gender'] ?? null;

                // Build STYLE-COLOR-SIZE combination
                $parts = [];
                if (!empty($brandSku)) {
                    $parts[] = $brandSku;
                }
                if (!empty($color)) {
                    $parts[] = preg_replace('/\s+/', '', $color);
                }
                if (!empty($size)) {
                    $parts[] = $size;
                }
                $combinedSku = implode('-', $parts);
            }

            $brandCreatedSku = [
                'style_group'  => $brandCreateData['style_group'] ?? null,
                'brand_sku'    => $brandSku,
                'combined_sku' => $combinedSku,
                'gender'       => $brandCreateData['gender'] ?? null,
                'color'        => $brandCreateData['color'] ?? null,
                'size'         => $brandCreateData['size'] ?? null
            ];

            $data = [
                'batch_id'          => $batchId,
                'linked_sku'        => $linkedSku,
                'brand_created_sku' => json_encode($brandCreatedSku, JSON_UNESCAPED_UNICODE),
                'source_code'       => $sourceCode,
                'status'            => $status,
                'created_date'      => date('Y-m-d H:i:s')
            ];

            $connection->insert($manualMatchTable, $data);
            return true;

        } catch (\Exception $e) {
            $this->log('saveManualMatch error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get manual match records for a batch
     * 
     * @param int $batchId
     * @return array
     */
    public function getManualMatches($batchId)
    {
        try {
            $connection = $this->resource->getConnection();
            $manualMatchTable = $this->resource->getTableName('artm_brandinventory_manual_match');

            $matches = $connection->fetchAll(
                "SELECT * FROM {$manualMatchTable} WHERE batch_id = ? ORDER BY created_date DESC",
                [$batchId]
            );

            // Decode JSON fields
            foreach ($matches as &$match) {
                if (!empty($match['brand_created_sku'])) {
                    $match['brand_created_sku'] = json_decode($match['brand_created_sku'], true);
                }
            }

            return $matches;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Fetch temp row data to extract fields for manual match
     * 
     * @param int $tempId
     * @return array
     */
    public function getTempRowData($tempId)
    {
        try {
            $connection = $this->resource->getConnection();
            $tempTable = $this->resource->getTableName('artm_brandinventory_temp');

            $row = $connection->fetchRow(
                "SELECT entity_id, batch_id, potential_sku, color, style, size, style_code, product_data, linked_sku 
                 FROM {$tempTable} 
                 WHERE entity_id = ?",
                [$tempId]
            );

            if (!$row) {
                return null;
            }

            // Decode potential_sku if JSON
            if (!empty($row['potential_sku'])) {
                $decoded = json_decode($row['potential_sku'], true);
                $row['potential_sku'] = is_array($decoded) ? $decoded : [$row['potential_sku']];
                // Get first potential SKU as brand_sku
            }
            $row['brand_sku'] = $row['style'] ?? '';

            // Decode product_data to extract gender if available
            if (!empty($row['product_data'])) {
                $productData = json_decode($row['product_data'], true);
                // Extract gender from product_data if available
                $row['gender'] = $this->extractGenderFromProductData($productData);
            }

            return $row;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract gender from product data
     * 
     * @param array $productData
     * @return string|null
     */
    private function extractGenderFromProductData($productData)
    {
        if (!is_array($productData)) {
            return null;
        }

        // Search for gender field (case-insensitive)
        foreach ($productData as $key => $value) {
            if (stripos($key, 'gender') !== false) {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Get batch source code
     * 
     * @param int $batchId
     * @return string|null
     */
    public function getBatchSourceCode($batchId)
    {
        try {
            $connection = $this->resource->getConnection();
            $batchTable = $this->resource->getTableName('artm_brand_inventory');

            $sourceCode = $connection->fetchOne(
                "SELECT source_code FROM {$batchTable} WHERE id = ?",
                [$batchId]
            );

            return $sourceCode;

        } catch (\Exception $e) {
            return null;
        }
    }

    protected function log($message)
    {
        if ($this->logger) {
            $this->logger->debug($message);
        }
    }
}
