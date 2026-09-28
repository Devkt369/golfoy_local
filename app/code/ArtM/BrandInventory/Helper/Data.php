<?php
namespace ArtM\BrandInventory\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;

class Data extends AbstractHelper
{
    protected $resource;

    public function __construct(
        Context $context,
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
        parent::__construct($context);
    }

    /**
     * Get batch items for sync
     */
    public function getBatchItems($batchId)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_temp');

        return $connection->fetchAll(
            "SELECT entity_id, potential_sku, linked_sku, update_qty, qty, size, color 
             FROM {$table} 
             WHERE batch_id = ?",
            [$batchId]
        );
    }

    /**
     * Get unmatched items count
     */
    public function getUnmatchedCount($batchId)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_temp');

        return (int)$connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} 
             WHERE batch_id = ? AND (linked_sku IS NULL OR linked_sku = '')",
            [$batchId]
        );
    }

    /**
     * Get match summary
     */
    public function getMatchSummary($batchId)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_temp');

        $total = (int)$connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} WHERE batch_id = ?",
            [$batchId]
        );

        $matched = (int)$connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} 
             WHERE batch_id = ? AND linked_sku IS NOT NULL AND linked_sku != ''",
            [$batchId]
        );

        return [
            'total' => $total,
            'matched' => $matched,
            'pending' => $total - $matched
        ];
    }

    /**
     * Create potential SKU combinations
     */
    public function createPotentialSku($params,$limit)
    {
        $allOptions = [];

        foreach ($params as $param) {
            $value = trim($param['value']);
            if (!empty($param['variation'])) {
                $allOptions[] = $this->generateValueVariations($value);
            } else {
                $allOptions[] = $this->generateFixedVariations($value);
            }
        }

        $results = [];
        $this->generateCombinations($allOptions, 0, [], $results, $limit);
        return array_values(array_unique($results));
    }

    /**
     * Fixed values (no variation)
     */
    private function generateFixedVariations($value)
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/\s+/', '-', $value);
        $value = preg_replace('/[^A-Z0-9-]/', '', $value);

        $results = [];
        $results[] = $value;
        $results[] = str_replace('-', '', $value);

        return array_values(array_unique($results));
    }

    /**
     * Dynamic variations
     */
    private function generateValueVariations($value)
    {
        $original = strtoupper(trim($value));
        $results = [];

        $noSpace = preg_replace('/\s+/', '', $original);
        $hyphen = preg_replace('/\s+/', '-', $original);
        $words = array_filter(explode(' ', $original));

        $initials = '';
        foreach ($words as $word) {
            $initials .= $word[0];
        }

        $wordFirstLast = '';
        foreach ($words as $word) {
            $word = trim($word);
            if ($word == '') continue;
            if (strlen($word) == 1) {
                $wordFirstLast .= $word;
            } else {
                $wordFirstLast .= $word[0] . substr($word, -1);
            }
        }

        $edgeLetters = '';
        if (!empty($words)) {
            $firstWord = reset($words);
            $lastWord = end($words);
            $edgeLetters = $firstWord[0] . substr($lastWord, -1);
        }

        // NEW: First two letters of first word + first and last letters of last word
        // e.g. "SHARK GREY" → "SH" + "GY" = "SHGY"
        $twoPlusFirstLast = '';
        if (count($words) >= 2) {
            $firstWord = reset($words);
            $lastWord = end($words);
            $firstTwo = substr($firstWord, 0, 2);           // "SH" from "SHARK"
            $lastFirstLast = $lastWord[0] . substr($lastWord, -1);  // "GY" from "GREY"
            $twoPlusFirstLast = $firstTwo . $lastFirstLast;         // "SHGY"
        }

        $results[] = $noSpace;
        $results[] = $hyphen;

        if ($initials != '') $results[] = $initials;
        if ($wordFirstLast != '') $results[] = $wordFirstLast;
        if ($edgeLetters != '') $results[] = $edgeLetters;
        if ($twoPlusFirstLast != '') $results[] = $twoPlusFirstLast;  // ← NEW

        if (count($words) == 1 && strlen($noSpace) <= 4) {
            $results[] = substr($noSpace, 0, 1);
            if (strlen($noSpace) >= 2) {
                $results[] = substr($noSpace, 0, 1) . substr($noSpace, -1);
            }
        }

        $final = [];
        foreach ($results as $v) {
            $v = strtoupper(trim($v));
            $v = preg_replace('/\s+/', '-', $v);
            $v = preg_replace('/[^A-Z0-9-]/', '', $v);
            if ($v != '') $final[] = $v;
        }

        return array_values(array_unique($final));
    }

    /**
     * Recursive combinations
     */
    private function generateCombinations($allOptions, $index, $current, &$results, $limit)
    {
        if (count($results) >= $limit) return;

        if ($index >= count($allOptions)) {
            $results[] = implode('-', $current);
            return;
        }

        foreach ($allOptions[$index] as $option) {
            if (count($results) >= $limit) break;
            $newCurrent = $current;
            $newCurrent[] = $option;
            $this->generateCombinations($allOptions, $index + 1, $newCurrent, $results, $limit);
        }
    }

    private function getSourceTypeByBatchId($batchId)
    {
        $connection = $this->resource->getConnection();
        
        $sourceCode = $connection->fetchOne(
            "SELECT source_code FROM {$this->resource->getTableName('artm_brand_inventory')} WHERE id = ? LIMIT 1",
            [$batchId]
        );
        
        if (!$sourceCode) return 'style_composite'; // default fallback
        
        $sourceType = $connection->fetchOne(
            "SELECT source_type FROM {$this->resource->getTableName('artm_brandinventory_selected_attr')} WHERE source = ? LIMIT 1",
            [$sourceCode]
        );
        
        return $sourceType ?: 'style_composite';
    }


}