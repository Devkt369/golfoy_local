<?php
namespace ArtM\MassProducts\Model\Import;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;

class DraftCreator
{
    private $resource;
    private $skuResolver;
    private $eavConfig;
    private $optionMaps = [];

    public function __construct(
        ResourceConnection $resource,
        SkuResolver $skuResolver,
        EavConfig $eavConfig
    )
    {
        $this->resource = $resource;
        $this->skuResolver = $skuResolver;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Save CSV rows as draft records.
     *
     * Drafts are raw storage — no Magento attribute validation is performed here.
     * TODO: Required attribute validation will run during QUEUE stage, not draft stage.
     *
     * @param array  $rows      Output of UploadFileReader::extractRows()
     * @param array  $columnMap [csv_column => attribute_code]
     * @param string $batchId
     * @return array{saved:int,created_count:int,update_count:int}
     */
    public function createDraft(array $rows, array $columnMap, string $batchId, string $draftedBy = 'system'): array
    {
        if (empty($rows)) {
            return [
                'saved' => 0,
                'created_count' => 0,
                'update_count' => 0,
            ];
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $toInsert = [];
        $createdCount = 0;
        $updateCount = 0;
        $connection = $this->resource->getConnection();
        $attrSetTable = $connection->getTableName('eav_attribute_set');
        $cachedSetIds = [];


        // --- Parent/Child Validation and Grouping ---
        $skuRows = [];
        $skuToRowIdx = [];
        $parentMap = [];
        $childMap = [];
        $allSkus = [];
        foreach ($rows as $i => $row) {
            $data = isset($row['data']) && is_array($row['data']) ? $row['data'] : [];
            $sku = '';
            $parent = '';
            $child = '';
            $attributeSet = '';
            foreach ($data as $key => $value) {
                $k = strtolower(trim((string)$key));
                if ($k === 'sku') $sku = trim((string)$value);
                if ($k === 'parent') $parent = trim((string)$value);
                if ($k === 'child') $child = trim((string)$value);
                if ($k === 'attribute_set') $attributeSet = trim((string)$value);
            }
            if ($sku === '') {
                throw new \RuntimeException('SKU missing in one or more rows.');
            }
            if ($attributeSet === '') {
                throw new \RuntimeException('attribute_set missing in one or more rows.');
            }
            $skuRows[$sku] = [
                'row' => $row,
                'parent' => $parent,
                'child' => $child,
                'attribute_set' => $attributeSet,
                'row_idx' => $i
            ];
            $skuToRowIdx[$sku] = $i;
            $allSkus[] = $sku;
            if ($parent !== '') {
                $parentMap[$sku] = $parent;
            }
            if ($child !== '') {
                $childMap[$sku] = $child;
            }
        }

        // Build sets of SKUs identified as configurable (parents) and simples (children)
        // A SKU is a configurable if: it has a 'child' column set OR another row has 'parent' = this SKU
        // A SKU is a simple child if: it has a 'parent' column set OR another row has 'child' = this SKU
        $configurableSkus = []; // SKUs that are configurable/parent
        $simpleChildSkus  = []; // SKUs that are simple children
        foreach ($skuRows as $sku => $info) {
            if ($info['child'] !== '') {
                $configurableSkus[$sku] = true;
                $simpleChildSkus[$info['child']] = true;
            }
            if ($info['parent'] !== '') {
                $simpleChildSkus[$sku] = true;
                $configurableSkus[$info['parent']] = true;
            }
        }

        // Validation
        foreach ($skuRows as $sku => $info) {
            $isConfigurable = isset($configurableSkus[$sku]);
            $isSimpleChild  = isset($simpleChildSkus[$sku]);

            if ($isConfigurable && $isSimpleChild) {
                throw new \RuntimeException(
                    "Row validation error: SKU '$sku' is identified as both a configurable product (parent) and a simple child. "
                    . "A SKU cannot be both a parent and a child."
                );
            }
            // Configurable products cannot have a 'parent' column set
            if ($isConfigurable && $info['parent'] !== '') {
                throw new \RuntimeException(
                    "Row validation error: SKU '$sku' is a configurable product but has a 'parent' value set. "
                    . "Configurable products cannot have a parent."
                );
            }
            // Simple child products cannot have a 'child' column set
            if ($isSimpleChild && $info['child'] !== '') {
                throw new \RuntimeException(
                    "Row validation error: SKU '$sku' is a simple child product but has a 'child' value set. "
                    . "Simple products associated with a configurable cannot themselves have a child."
                );
            }
            // Referenced parent must exist in CSV
            if ($info['parent'] !== '' && !isset($skuRows[$info['parent']])) {
                throw new \RuntimeException(
                    "Row validation error: SKU '$sku' references parent '{$info['parent']}' which does not exist in the CSV."
                );
            }
            // Referenced child must exist in CSV
            if ($info['child'] !== '' && !isset($skuRows[$info['child']])) {
                throw new \RuntimeException(
                    "Row validation error: SKU '$sku' references child '{$info['child']}' which does not exist in the CSV."
                );
            }
        }

// Grouping for draft table: parents first, then all their children together
        $groupedRows = [];
        $added = [];
        // Add all configurables and their children grouped together
        foreach ($skuRows as $sku => $info) {
            if (isset($configurableSkus[$sku]) && !isset($added[$sku])) {
                $groupedRows[] = $sku;
                $added[$sku] = true;
                // Add all children that declare this SKU as their parent
                foreach ($skuRows as $childSku => $childInfo) {
                    if ($childInfo['parent'] === $sku && !isset($added[$childSku])) {
                        $groupedRows[] = $childSku;
                        $added[$childSku] = true;
                    }
                }
                // Also add the child declared via 'child' column on this row (if not yet added)
                if ($info['child'] !== '' && !isset($added[$info['child']])) {
                    $groupedRows[] = $info['child'];
                    $added[$info['child']] = true;
                }
            }
        }
        // Add any remaining standalone simple SKUs
        foreach ($skuRows as $sku => $info) {
            if (!isset($added[$sku])) {
                $groupedRows[] = $sku;
            }
        }

        // Now, build $toInsert in grouped order, and mark for display
        foreach ($groupedRows as $sku) {
            $info = $skuRows[$sku];
            $row = $info['row'];
            $rowNumber = (int)($row['row_number'] ?? 0);
            $data = isset($row['data']) && is_array($row['data']) ? $row['data'] : [];

            $attributeSet = $info['attribute_set'];
            $operationType = $this->skuResolver->resolveOperation($sku);
            if ($operationType === 'update') {
                $updateCount++;
            } else {
                $createdCount++;
            }

            // Build mapped_data: apply column mapping, but skip 'parent' and 'child' columns
            $mappedData = [];
            foreach ($columnMap as $csvCol => $attrCode) {
                $csvColLower = strtolower(trim((string)$csvCol));
                if ($csvColLower === 'parent' || $csvColLower === 'child') {
                    continue;
                }
                foreach ($data as $key => $value) {
                    if (strtolower(trim((string)$key)) === $csvColLower) {
                        $mappedData[$attrCode] = $this->normalizeMappedAttributeValue((string)$attrCode, $value);
                        break;
                    }
                }
            }

            $normalizedSet = strtolower(trim($attributeSet));
            if (!array_key_exists($normalizedSet, $cachedSetIds)) {
                $cachedSetIds[$normalizedSet] = (int)$connection->fetchOne(
                    $connection->select()
                        ->from($attrSetTable, ['attribute_set_id'])
                        ->where('LOWER(attribute_set_name) = ?', $normalizedSet)
                        ->limit(1)
                );
            }
            $attributeSetId = (int)$cachedSetIds[$normalizedSet];

            $productName = '';
            if (isset($mappedData['name'])) {
                $productName = trim((string)$mappedData['name']);
            }
            if ($productName === '' && isset($mappedData['product_name'])) {
                $productName = trim((string)$mappedData['product_name']);
            }
            if ($productName === '' && isset($data['name'])) {
                $productName = trim((string)$data['name']);
            }
            if ($productName === '' && isset($data['product_name'])) {
                $productName = trim((string)$data['product_name']);
            }

            $productType = 'simple';
            if (!empty($mappedData['type_id'])) {
                $productType = trim((string)$mappedData['type_id']);
            } elseif (!empty($data['type_id'])) {
                $productType = trim((string)$data['type_id']);
            }
            if ($productType === '') {
                $productType = 'simple';
            }

            $toInsert[] = [
                'batch_id'         => $batchId,
                'row_number'       => $rowNumber,
                'sku'              => $sku,
                'parent_sku'       => $info['parent'],
                'child_sku'        => $info['child'],
                'product_name'     => $productName,
                'attribute_set'    => $attributeSet,
                'attribute_set_id' => $attributeSetId > 0 ? $attributeSetId : null,
                'product_type'     => $productType,
                'operation_type'   => $operationType,
                'mapped_data'      => json_encode($mappedData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'raw_data'         => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                'status'           => 'incomplete',
                'missing_attributes_count' => 0,
                'drafted_by'       => trim($draftedBy) !== '' ? trim($draftedBy) : 'system',
                'last_uploaded_by' => trim($draftedBy) !== '' ? trim($draftedBy) : 'system',
                'last_uploaded_at' => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        if (!empty($toInsert)) {
            $table = $connection->getTableName('artm_massproducts_draft');
            $connection->insertMultiple($table, $toInsert);
        }

        return [
            'saved' => count($toInsert),
            'created_count' => $createdCount,
            'update_count' => $updateCount,
        ];
    }

    private function normalizeMappedAttributeValue(string $attributeCode, $value)
    {
        $attrCode = trim($attributeCode);
        if ($attrCode === '') {
            return $value;
        }

        try {
            $attribute = $this->eavConfig->getAttribute('catalog_product', $attrCode);
            if (!$attribute || !(int)$attribute->getId()) {
                return $value;
            }

            $input = (string)$attribute->getFrontendInput();
            if (!in_array($input, ['select', 'multiselect', 'boolean'], true)) {
                return $value;
            }

            if ($input === 'boolean') {
                $raw = strtolower(trim((string)$value));
                if (in_array($raw, ['1', 'yes', 'true', 'y'], true)) {
                    return '1';
                }
                if (in_array($raw, ['0', 'no', 'false', 'n'], true)) {
                    return '0';
                }
                return $value;
            }

            $map = $this->getOptionLabelValueMap($attrCode, $attribute);
            if (empty($map)) {
                return $value;
            }

            if ($input === 'multiselect') {
                $rawParts = preg_split('/\s*[;,|]\s*/', (string)$value);
                if (!is_array($rawParts)) {
                    return $value;
                }

                $resolved = [];
                foreach ($rawParts as $part) {
                    $token = trim((string)$part);
                    if ($token === '') {
                        continue;
                    }

                    $normalized = $this->normalizeOptionText($token);
                    if (isset($map[$normalized])) {
                        $resolved[] = (string)$map[$normalized];
                        continue;
                    }

                    if (ctype_digit($token)) {
                        $resolved[] = $token;
                        continue;
                    }

                    $resolved[] = $token;
                }

                return implode(',', array_values(array_unique($resolved)));
            }

            $raw = trim((string)$value);
            if ($raw === '') {
                return $value;
            }
            if (ctype_digit($raw)) {
                return $raw;
            }

            $normalized = $this->normalizeOptionText($raw);
            if (isset($map[$normalized])) {
                return (string)$map[$normalized];
            }
        } catch (\Throwable $e) {
            return $value;
        }

        return $value;
    }

    private function getOptionLabelValueMap(string $attributeCode, $attribute): array
    {
        if (isset($this->optionMaps[$attributeCode])) {
            return $this->optionMaps[$attributeCode];
        }

        $map = [];
        try {
            if ($attribute && $attribute->usesSource()) {
                $options = (array)$attribute->getSource()->getAllOptions(false);
                foreach ($options as $option) {
                    if (!is_array($option)) {
                        continue;
                    }

                    $optValue = isset($option['value']) ? trim((string)$option['value']) : '';
                    $optLabel = isset($option['label']) ? trim((string)$option['label']) : '';
                    if ($optValue === '' || $optLabel === '') {
                        continue;
                    }

                    $map[$this->normalizeOptionText($optLabel)] = $optValue;
                }
            }
        } catch (\Throwable $e) {
            $map = [];
        }

        $this->optionMaps[$attributeCode] = $map;
        return $map;
    }

    private function normalizeOptionText(string $value): string
    {
        $text = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);

        return (string)$text;
    }
}
