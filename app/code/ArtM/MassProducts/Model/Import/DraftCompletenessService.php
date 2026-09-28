<?php
namespace ArtM\MassProducts\Model\Import;

use Magento\Framework\App\ResourceConnection;

class DraftCompletenessService
{
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Compute completeness using required attributes from attribute set.
     * Returns [status, missing_count, filled_required, total_required, progress_percent, missing_codes].
     */
    public function computeDraftCompleteness(array $draftRow): array
    {
        $attributeSetId = (int)($draftRow['attribute_set_id'] ?? 0);
        $draftId = (int)($draftRow['entity_id'] ?? 0);
        $mappedData = [];
        if (!empty($draftRow['mapped_data'])) {
            $decoded = json_decode((string)$draftRow['mapped_data'], true);
            if (is_array($decoded)) {
                $mappedData = $decoded;
            }
        }

        if ($attributeSetId <= 0 || $draftId <= 0) {
            return [
                'status' => 'incomplete',
                'missing_count' => 0,
                'filled_required' => 0,
                'total_required' => 0,
                'progress_percent' => 0,
                'missing_codes' => [],
            ];
        }

        $connection = $this->resource->getConnection();
        $eavAttrTable = $connection->getTableName('eav_attribute');
        $eavEntityAttrTable = $connection->getTableName('eav_entity_attribute');
        $catalogEavAttrTable = $connection->getTableName('catalog_eav_attribute');
        $draftValuesTable = $connection->getTableName('artm_massproducts_draft_values');
        $hasDraftValuesTable = $connection->isTableExists($draftValuesTable);
        $hasCatalogEavAttrTable = $connection->isTableExists($catalogEavAttrTable);

        $select = $connection->select()
            ->from(['a' => $eavAttrTable], ['attribute_code'])
            ->joinInner(['ea' => $eavEntityAttrTable], 'ea.attribute_id = a.attribute_id', [])
            ->where('ea.attribute_set_id = ?', $attributeSetId)
            ->where('a.is_required = ?', 1);

        if ($hasCatalogEavAttrTable) {
            $select->joinLeft(
                ['cea' => $catalogEavAttrTable],
                'cea.attribute_id = a.attribute_id',
                ['apply_to' => 'apply_to', 'is_visible' => 'is_visible']
            );
            $select->where('COALESCE(cea.is_visible, 1) = ?', 1);
        }

        $requiredAttributes = $connection->fetchAll($select);

        $requiredCodes = [];
        $systemAttributes = [
            'dynamic_price',
            'dynamic_sku', 
            'dynamic_weight',
            'price_view',
            'shipment_type',
            'ship_bundle_items',
            'giftcard_amounts',
            'links_purchased_separately',
            'links_title',
            'samples_title',
            'created_at',
            'updated_at',
            'price_type',
            'sku_type',
            'weight_type',
            'gift_code_pattern',
            'gift_type',
            'gift_price_type',
            'gift_template_ids'
        ];
        
        foreach ($requiredAttributes as $row) {
            $code = trim((string)($row['attribute_code'] ?? ''));
            $applyTo = (string)($row['apply_to'] ?? '');
            
            if ($code === '') {
                continue;
            }
            
            // Skip system attributes
            if (in_array($code, $systemAttributes, true)) {
                continue;
            }
            
            // Check apply_to
            if ($applyTo !== '' && strpos($applyTo, 'simple') === false) {
                continue;
            }
            
            $requiredCodes[] = $code;
        }

        if (empty($requiredCodes)) {
            return [
                'status' => 'ready',
                'missing_count' => 0,
                'filled_required' => 0,
                'total_required' => 0,
                'progress_percent' => 100,
                'missing_codes' => [],
            ];
        }

        $savedValuesRows = [];
        if ($hasDraftValuesTable) {
            $savedValuesRows = $connection->fetchAll(
                $connection->select()
                    ->from($draftValuesTable, ['attribute_code', 'value'])
                    ->where('draft_id = ?', $draftId)
                    ->where('store_id = ?', 0)
            );
        }
        $savedValues = [];
        foreach ($savedValuesRows as $row) {
            $savedValues[(string)$row['attribute_code']] = $row['value'];
        }

        $filled = 0;
        $missingCodes = [];
        foreach ($requiredCodes as $code) {
            $value = null;
            if (array_key_exists($code, $savedValues)) {
                $value = $savedValues[$code];
            } elseif (array_key_exists($code, $mappedData)) {
                $value = $mappedData[$code];
            }

            if ($this->hasMeaningfulValue($value)) {
                $filled++;
            } else {
                $missingCodes[] = $code;
            }
        }

        $total = count($requiredCodes);
        $missingCount = $total - $filled;
        $progress = $total > 0 ? (int)floor(($filled / $total) * 100) : 100;

        return [
            'status' => $missingCount > 0 ? 'incomplete' : 'ready',
            'missing_count' => $missingCount,
            'filled_required' => $filled,
            'total_required' => $total,
            'progress_percent' => $progress,
            'missing_codes' => $missingCodes,
        ];
    }

    private function hasMeaningfulValue($value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_array($value)) {
            return !empty($value);
        }
        return trim((string)$value) !== '';
    }
}
