<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use ArtM\MassProducts\Model\Import\DraftCompletenessService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;
use Magento\Eav\Model\Config as EavConfig;

class TabMatrix extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;
    private $completenessService;
    private $eavConfig;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        DraftCompletenessService $completenessService,
        EavConfig $eavConfig
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->completenessService = $completenessService;
        $this->eavConfig = $eavConfig;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            $valueTable = $connection->getTableName('artm_massproducts_draft_values');
            $setTable = $connection->getTableName('eav_attribute_set');
            $draftSchema = $connection->describeTable($draftTable);
            $hasAttributeSetIdColumn = isset($draftSchema['attribute_set_id']);
            $hasDraftValuesTable = $connection->isTableExists($valueTable);

            $drafts = $connection->fetchAll(
                $connection->select()
                    ->from($draftTable)
                    ->where('status IN (?)', ['draft', 'incomplete'])
                    ->orWhere('status IS NULL')
                    ->orWhere('status = ?', '')
                    ->order('attribute_set ASC')
                    ->order('entity_id ASC')
            );

            if (empty($drafts)) {
                return $result->setData(['success' => true, 'groups' => []]);
            }

            // Resolve missing attribute_set_id from attribute_set name for older rows.
            foreach ($drafts as &$draft) {
                $setId = $hasAttributeSetIdColumn ? (int)($draft['attribute_set_id'] ?? 0) : 0;
                $setName = trim((string)($draft['attribute_set'] ?? ''));
                if ($setId <= 0 && $setName !== '') {
                    $setId = (int)$connection->fetchOne(
                        $connection->select()
                            ->from($setTable, ['attribute_set_id'])
                            ->where('LOWER(attribute_set_name) = ?', strtolower($setName))
                            ->limit(1)
                    );
                    if ($setId > 0) {
                        $draft['attribute_set_id'] = $setId;
                        if ($hasAttributeSetIdColumn) {
                            $connection->update(
                                $draftTable,
                                ['attribute_set_id' => $setId],
                                ['entity_id = ?' => (int)$draft['entity_id']]
                            );
                        }
                    }
                }
            }
            unset($draft);

            $draftIds = array_values(array_filter(array_map(static function ($d) {
                return (int)($d['entity_id'] ?? 0);
            }, $drafts)));

            $valueRows = [];
            if (!empty($draftIds) && $hasDraftValuesTable) {
                $valueRows = $connection->fetchAll(
                    $connection->select()
                        ->from($valueTable, ['draft_id', 'attribute_code', 'value'])
                        ->where('draft_id IN (?)', $draftIds)
                        ->where('store_id = ?', 0)
                );
            }

            $valuesByDraft = [];
            foreach ($valueRows as $vr) {
                $did = (int)$vr['draft_id'];
                if (!isset($valuesByDraft[$did])) {
                    $valuesByDraft[$did] = [];
                }
                $valuesByDraft[$did][(string)$vr['attribute_code']] = $vr['value'];
            }

            // Load media items per draft
            $mediaByDraft = [];
            $mediaTable   = $connection->getTableName('artm_massproducts_draft_media');
            if ($connection->isTableExists($mediaTable) && !empty($draftIds)) {
                $mediaBaseUrl = rtrim(
                    $this->_url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]),
                    '/'
                );
                $mediaRows = $connection->fetchAll(
                    $connection->select()
                        ->from($mediaTable, ['media_id', 'draft_id', 'file_path', 'roles', 'position'])
                        ->where('draft_id IN (?)', $draftIds)
                        ->order('draft_id ASC')
                        ->order('position ASC')
                );
                foreach ($mediaRows as $mr) {
                    $did = (int)$mr['draft_id'];
                    if (!isset($mediaByDraft[$did])) {
                        $mediaByDraft[$did] = [];
                    }
                    $rolesStr  = (string)($mr['roles'] ?? '');
                    $rolesArr  = $rolesStr !== '' ? array_values(array_filter(explode(',', $rolesStr))) : [];
                    $filePath  = (string)($mr['file_path'] ?? '');
                    $mediaByDraft[$did][] = [
                        'media_id'  => (int)$mr['media_id'],
                        'file_path' => $filePath,
                        'url'       => $mediaBaseUrl . '/' . ltrim($filePath, '/'),
                        'roles'     => $rolesArr,
                        'position'  => (int)$mr['position'],
                    ];
                }
            }

            $attributesBySet = [];
            $msiSources = $this->getMsiSources();
            $groups = [];
            foreach ($drafts as $draft) {
                $draftId = (int)$draft['entity_id'];
                $setId = (int)($draft['attribute_set_id'] ?? 0);
                $setName = trim((string)($draft['attribute_set'] ?? ''));
                if ($setName === '') {
                    $setName = 'Unknown';
                }
                // Group by attribute_set value from draft table.
                $key = 'name_' . strtolower($setName);

                if (!isset($attributesBySet[$key])) {
                    $attributesBySet[$key] = $this->getAttributeSetAttributes($setId);
                }

                $summary = $this->completenessService->computeDraftCompleteness($draft);
                $newStatus = ((int)$summary['missing_count'] > 0) ? 'incomplete' : 'draft';
                $newMissing = (int)$summary['missing_count'];
                if ((string)($draft['status'] ?? '') !== $newStatus || (int)($draft['missing_attributes_count'] ?? 0) !== $newMissing) {
                    $connection->update(
                        $draftTable,
                        [
                            'status' => $newStatus,
                            'missing_attributes_count' => $newMissing,
                            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
                        ],
                        ['entity_id = ?' => $draftId]
                    );
                    $draft['status'] = $newStatus;
                    $draft['missing_attributes_count'] = $newMissing;
                }

                $mapped = [];
                if (!empty($draft['mapped_data'])) {
                    $decoded = json_decode((string)$draft['mapped_data'], true);
                    if (is_array($decoded)) {
                        $mapped = $decoded;
                    }
                }

                $rowValues = [];
                foreach ($attributesBySet[$key] as $attr) {
                    $code = (string)$attr['attribute_code'];
                    $rowValues[$code] = $valuesByDraft[$draftId][$code] ?? ($mapped[$code] ?? '');
                }

                $rowMsiValues = [];
                if (isset($valuesByDraft[$draftId]) && is_array($valuesByDraft[$draftId])) {
                    foreach ($valuesByDraft[$draftId] as $code => $value) {
                        $attributeCode = (string)$code;
                        if (strpos($attributeCode, 'msi_qty__') === 0 && trim((string)$value) !== '') {
                            $sourceCode = substr($attributeCode, 9);
                            if ($sourceCode !== '') {
                                $rowMsiValues[$sourceCode] = (string)$value;
                            }
                        }
                    }
                }
                if (empty($rowMsiValues)) {
                    foreach ($mapped as $code => $value) {
                        $attributeCode = (string)$code;
                        if (strpos($attributeCode, 'msi_qty__') !== 0) {
                            continue;
                        }
                        $sourceCode = substr($attributeCode, 9);
                        $qtyValue = trim((string)$value);
                        if ($sourceCode !== '' && $qtyValue !== '') {
                            $rowMsiValues[$sourceCode] = $qtyValue;
                        }
                    }
                }
                if (empty($rowMsiValues) && !empty($draft['raw_data'])) {
                    $decodedRaw = json_decode((string)$draft['raw_data'], true);
                    if (is_array($decodedRaw)) {
                        foreach ($decodedRaw as $code => $value) {
                            $attributeCode = (string)$code;
                            if (strpos($attributeCode, 'msi_qty__') !== 0) {
                                continue;
                            }
                            $sourceCode = substr($attributeCode, 9);
                            $qtyValue = trim((string)$value);
                            if ($sourceCode !== '' && $qtyValue !== '') {
                                $rowMsiValues[$sourceCode] = $qtyValue;
                            }
                        }
                    }
                }

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'attribute_set_id' => $setId,
                        'attribute_set_name' => $setName,
                        'attributes' => $attributesBySet[$key],
                        'msi_sources' => $msiSources,
                        'rows' => [],
                        'queueable' => true,
                    ];
                }

                if ((int)$summary['missing_count'] > 0) {
                    $groups[$key]['queueable'] = false;
                }

                $parentSku = trim((string)($draft['parent_sku'] ?? ''));
                $childSku = trim((string)($draft['child_sku'] ?? ''));
                $variationAttributes = [];
                $displayProductName = trim((string)($draft['product_name'] ?? ''));
                if (!empty($draft['raw_data'])) {
                    $decodedRaw = json_decode((string)$draft['raw_data'], true);
                    if (is_array($decodedRaw)) {
                        if ($parentSku === '' && isset($decodedRaw['parent'])) {
                            $parentSku = trim((string)$decodedRaw['parent']);
                        }
                        if ($childSku === '' && isset($decodedRaw['child'])) {
                            $childSku = trim((string)$decodedRaw['child']);
                        }
                        if ($displayProductName === '') {
                            $displayProductName = trim((string)($decodedRaw['name'] ?? $decodedRaw['product_name'] ?? ''));
                        }
                        $variationAttributes = $this->normalizeVariationAttributes($decodedRaw['variation_attributes'] ?? []);
                    }
                }
                if ($displayProductName === '') {
                    $displayProductName = trim((string)($mapped['name'] ?? $mapped['product_name'] ?? ''));
                }

                $groups[$key]['rows'][] = [
                    'entity_id' => $draftId,
                    'sku' => (string)($draft['sku'] ?? ''),
                    'parent' => $parentSku,
                    'child' => $childSku,
                    'drafted_by' => (string)($draft['drafted_by'] ?? $draft['last_uploaded_by'] ?? ''),
                    'ready_by' => (string)($draft['ready_by'] ?? ''),
                    'added_by' => (string)($draft['added_by'] ?? ''),
                    'variation_attributes' => $variationAttributes,
                    'product_name' => $displayProductName,
                    'operation_type' => (string)($draft['operation_type'] ?? 'create'),
                    'status' => (string)($draft['status'] ?? 'incomplete'),
                    'missing_attributes_count' => (int)($draft['missing_attributes_count'] ?? 0),
                    'values' => $rowValues,
                    'msi_values' => $rowMsiValues,
                    'media_items' => $mediaByDraft[$draftId] ?? [],
                ];
            }

            return $result->setData([
                'success' => true,
                'groups' => array_values($groups),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeVariationAttributes($raw): array
    {
        $items = is_array($raw) ? $raw : preg_split('/[\n,]+/', (string)$raw);
        $normalized = [];

        foreach ((array)$items as $item) {
            $code = trim((string)$item);
            if ($code === '' || in_array($code, $normalized, true)) {
                continue;
            }
            $normalized[] = $code;
        }

        return $normalized;
    }

    private function getAttributeSetAttributes(int $attributeSetId): array
    {
        if ($attributeSetId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $entityAttribute = $connection->getTableName('eav_entity_attribute');
        $attribute = $connection->getTableName('eav_attribute');
        $catalogEavAttribute = $connection->getTableName('catalog_eav_attribute');
        $option = $connection->getTableName('eav_attribute_option');
        $optionValue = $connection->getTableName('eav_attribute_option_value');
        $cacheTable = $connection->getTableName('artm_massproducts_attrset_cache');
        $hasCacheTable = $connection->isTableExists($cacheTable);
        $hasCatalogEavAttribute = $connection->isTableExists($catalogEavAttribute);
        $catalogEavSchema = $hasCatalogEavAttribute ? $connection->describeTable($catalogEavAttribute) : [];

        if ($hasCacheTable) {
            // Temporarily disable cache to force fresh filtering
            /*
            $cachedJson = $connection->fetchOne(
                $connection->select()
                    ->from($cacheTable, ['attributes_json'])
                    ->where('attribute_set_id = ?', $attributeSetId)
                    ->limit(1)
            );
            if ($cachedJson) {
                $decoded = json_decode((string)$cachedJson, true);
                if (is_array($decoded)) {
                    // Old cache may only contain user-defined attributes. Rebuild if marker key is missing.
                    $cacheHasSystemMarker = !empty($decoded) && array_key_exists('is_user_defined', (array)$decoded[0]);
                    if ($cacheHasSystemMarker) {
                        $decoded = $this->filterAttributes($decoded);
                        $decoded = $this->enrichCachedAttributeOptions($decoded);
                        $decoded = $this->applyAssignmentFilter($attributeSetId, $decoded);
                        return $this->applySortOrder(0, $decoded);
                    }
                }
            }
            */
        }

        $setName = (string)$connection->fetchOne(
            $connection->select()
                ->from($connection->getTableName('eav_attribute_set'), ['attribute_set_name'])
                ->where('attribute_set_id = ?', $attributeSetId)
                ->limit(1)
        );

        $select = $connection->select()
            ->from(['ea' => $entityAttribute], ['sort_order'])
            ->joinInner(
                ['a' => $attribute],
                'a.attribute_id = ea.attribute_id',
                ['attribute_id', 'attribute_code', 'frontend_label', 'frontend_input', 'is_required', 'is_user_defined']
            )
            ->where('ea.attribute_set_id = ?', $attributeSetId)
            ->order('ea.sort_order ASC');

        if ($hasCatalogEavAttribute) {
            $ceaColumns = [
                'is_visible' => 'is_visible',
                'apply_to' => 'apply_to',
            ];

            if (isset($catalogEavSchema['is_configurable'])) {
                $ceaColumns['is_configurable'] = 'is_configurable';
            }
            if (isset($catalogEavSchema['is_global'])) {
                $ceaColumns['is_global'] = 'is_global';
            }

            $select->joinLeft(
                ['cea' => $catalogEavAttribute],
                'cea.attribute_id = a.attribute_id',
                $ceaColumns
            );
            $select->where('COALESCE(cea.is_visible, 1) = ?', 1);
        }

        $rows = $connection->fetchAll($select);

        $attributes = [];
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
            'samples_title'
        ];
        
        foreach ($rows as $row) {
            $code = (string)$row['attribute_code'];
            $input = (string)$row['frontend_input'];
            $label = trim((string)$row['frontend_label']);
            $applyTo = (string)($row['apply_to'] ?? '');
            
            // Skip system attributes
            if (in_array($code, $systemAttributes, true)) {
                continue;
            }
            
            // Check filters
            if ($input === '') {
                continue;
            }
            if ($label === '') {
                continue;
            }
            if ($applyTo !== '' && strpos($applyTo, 'simple') === false) {
                continue;
            }
            
            $options = [];
            if (in_array($input, ['select', 'multiselect', 'boolean'], true)) {
                $options = $this->resolveAttributeOptions(
                    (int)$row['attribute_id'],
                    $code,
                    $input,
                    $option,
                    $optionValue
                );
            }

            if ($label === '') {
                $label = $code;
            }

            $attributes[] = [
                'attribute_code' => $code,
                'frontend_label' => $label,
                'frontend_input' => $input,
                'is_required' => (int)$row['is_required'] === 1,
                'is_user_defined' => (int)$row['is_user_defined'] === 1,
                'is_global' => array_key_exists('is_global', $row) && $row['is_global'] !== null
                    ? (int)$row['is_global']
                    : null,
                'is_configurable' => array_key_exists('is_configurable', $row) && $row['is_configurable'] !== null
                    ? (int)$row['is_configurable']
                    : null,
                'options' => $options,
            ];
        }

        if ($hasCacheTable) {
            $connection->insertOnDuplicate(
                $cacheTable,
                [
                    'attribute_set_id' => $attributeSetId,
                    'attribute_set_name' => $setName !== '' ? $setName : ('set_' . $attributeSetId),
                    'attributes_json' => json_encode($attributes, JSON_UNESCAPED_UNICODE),
                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ],
                ['attribute_set_name', 'attributes_json', 'updated_at']
            );
        }

        $attributes = $this->applyAssignmentFilter($attributeSetId, $attributes);
        return $this->applySortOrder(0, $attributes);
    }

    private function filterAttributes(array $attributes): array
    {
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
            'samples_title'
        ];
        
        $filtered = [];
        foreach ($attributes as $attr) {
            $code = (string)($attr['attribute_code'] ?? '');
            $input = (string)($attr['frontend_input'] ?? '');
            $label = trim((string)($attr['frontend_label'] ?? ''));
            $applyTo = (string)($attr['apply_to'] ?? '');
            
            // Skip system attributes
            if (in_array($code, $systemAttributes, true)) {
                continue;
            }
            
            // Check filters
            if ($input === '') {
                continue;
            }
            if ($label === '') {
                continue;
            }
            if ($applyTo !== '' && strpos($applyTo, 'simple') === false) {
                continue;
            }
            
            $filtered[] = $attr;
        }
        
        return $filtered;
    }

    private function enrichCachedAttributeOptions(array $attributes): array
    {
        foreach ($attributes as &$attr) {
            $input = (string)($attr['frontend_input'] ?? '');
            $code = (string)($attr['attribute_code'] ?? '');
            if (!in_array($input, ['select', 'multiselect', 'boolean'], true) || $code === '') {
                continue;
            }

            $existing = $attr['options'] ?? [];
            if (!is_array($existing) || count($existing) <= 1) {
                $attr['options'] = $this->resolveAttributeOptions(0, $code, $input, '', '');
            }
        }
        unset($attr);

        return $attributes;
    }

    private function resolveAttributeOptions(
        int $attributeId,
        string $attributeCode,
        string $input,
        string $optionTable,
        string $optionValueTable
    ): array {
        if ($input === 'boolean') {
            return [
                ['value' => '1', 'label' => 'Yes'],
                ['value' => '0', 'label' => 'No'],
            ];
        }

        $options = [];

        // Prefer source model options: covers system attributes like status, visibility, tax_class_id, country_of_manufacture.
        try {
            $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
            if ($attribute && $attribute->usesSource()) {
                $sourceOptions = $attribute->getSource()->getAllOptions(false);
                if (is_array($sourceOptions)) {
                    foreach ($sourceOptions as $opt) {
                        if (!is_array($opt)) {
                            continue;
                        }
                        $value = isset($opt['value']) ? (string)$opt['value'] : '';
                        $label = isset($opt['label']) ? trim((string)$opt['label']) : '';
                        if ($value === '' && $label === '') {
                            continue;
                        }
                        $options[] = ['value' => $value, 'label' => $label !== '' ? $label : $value];
                    }
                }
            }
        } catch (\Throwable $e) {
            $options = [];
        }

        if (!empty($options)) {
            return $options;
        }

        // Fallback for custom attributes backed by eav_attribute_option tables.
        if ($attributeId > 0 && $optionTable !== '' && $optionValueTable !== '') {
            $connection = $this->resource->getConnection();
            return $connection->fetchAll(
                $connection->select()
                    ->from(['o' => $optionTable], ['value' => 'option_id'])
                    ->joinLeft(
                        ['ov' => $optionValueTable],
                        'ov.option_id = o.option_id AND ov.store_id = 0',
                        ['label' => 'value']
                    )
                    ->where('o.attribute_id = ?', $attributeId)
                    ->order('o.sort_order ASC')
            );
        }

        return [];
    }

    private function getMsiSources(): array
    {
        $connection = $this->resource->getConnection();
        $sourceTable = $connection->getTableName('inventory_source');
        if (!$connection->isTableExists($sourceTable)) {
            return [];
        }

        $schema = $connection->describeTable($sourceTable);
        $select = $connection->select()->from($sourceTable, ['source_code', 'name']);
        if (isset($schema['enabled'])) {
            $select->where('enabled = ?', 1);
        }
        $select->order('source_code ASC');

        $sources = $connection->fetchAll($select);
        $result = [];
        foreach ($sources as $source) {
            $sourceCode = trim((string)($source['source_code'] ?? ''));
            if ($sourceCode === '') {
                continue;
            }
            $sourceName = trim((string)($source['name'] ?? ''));
            $result[] = [
                'source_code' => $sourceCode,
                'name' => $sourceName !== '' ? $sourceName : $sourceCode,
            ];
        }

        return $result;
    }

    private function applySortOrder(int $attributeSetId, array $attributes): array
    {
        $connection = $this->resource->getConnection();
        $sortTable = $connection->getTableName('artm_massproducts_attr_sort');
        if (!$connection->isTableExists($sortTable)) {
            return $attributes;
        }

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($sortTable, ['attribute_code', 'sort_order'])
                ->where('attribute_set_id = ?', $attributeSetId)
        );
        if (empty($rows)) {
            return $attributes;
        }

        $sortMap = [];
        foreach ($rows as $row) {
            $sortMap[(string)$row['attribute_code']] = (int)$row['sort_order'];
        }

        usort($attributes, static function (array $a, array $b) use ($sortMap): int {
            $aCode = (string)($a['attribute_code'] ?? '');
            $bCode = (string)($b['attribute_code'] ?? '');
            $aGroup = !empty($a['is_user_defined']) ? 1 : 0;
            $bGroup = !empty($b['is_user_defined']) ? 1 : 0;

            if ($aGroup !== $bGroup) {
                return $aGroup <=> $bGroup;
            }

            $aOrder = array_key_exists($aCode, $sortMap) ? (int)$sortMap[$aCode] : PHP_INT_MAX;
            $bOrder = array_key_exists($bCode, $sortMap) ? (int)$sortMap[$bCode] : PHP_INT_MAX;

            if ($aOrder === $bOrder) {
                return $aCode <=> $bCode;
            }

            return $aOrder <=> $bOrder;
        });

        return $attributes;
    }

    private function applyAssignmentFilter(int $attributeSetId, array $attributes): array
    {
        $connection = $this->resource->getConnection();
        $assignmentTable = $connection->getTableName('artm_massproducts_attr_assignment');
        if (!$connection->isTableExists($assignmentTable)) {
            return $attributes;
        }

        $globalRows = $connection->fetchAll(
            $connection->select()
                ->from($assignmentTable, ['attribute_code', 'is_enabled'])
                ->where('attribute_set_id = ?', 0)
        );
        $setRows = $connection->fetchAll(
            $connection->select()
                ->from($assignmentTable, ['attribute_code', 'is_enabled'])
                ->where('attribute_set_id = ?', $attributeSetId)
        );

        $globalMap = [];
        foreach ($globalRows as $row) {
            $globalMap[(string)$row['attribute_code']] = (int)$row['is_enabled'];
        }

        $setMap = [];
        foreach ($setRows as $row) {
            $setMap[(string)$row['attribute_code']] = (int)$row['is_enabled'];
        }

        $filtered = [];
        foreach ($attributes as $attr) {
            $code = (string)($attr['attribute_code'] ?? '');
            if ($code === '') {
                continue;
            }

            $globalEnabled = array_key_exists($code, $globalMap) ? ((int)$globalMap[$code] === 1) : true;
            $setEnabled = array_key_exists($code, $setMap) ? ((int)$setMap[$code] === 1) : true;
            if ($globalEnabled && $setEnabled) {
                $filtered[] = $attr;
            }
        }

        return $filtered;
    }
}
