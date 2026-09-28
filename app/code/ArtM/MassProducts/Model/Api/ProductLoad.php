<?php
declare(strict_types=1);

namespace ArtM\MassProducts\Model\Api;

use ArtM\MassProducts\Api\ProductLoadInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductLoad implements ProductLoadInterface
{
    private const CONFIG_PATH_ATTRIBUTES = 'artm_massproducts/api/product_load_attributes';

    private ProductCollectionFactory $productCollectionFactory;
    private ResourceConnection $resource;
    private ScopeConfigInterface $scopeConfig;
    private EavConfig $eavConfig;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ResourceConnection $resource,
        ScopeConfigInterface $scopeConfig,
        EavConfig $eavConfig
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resource = $resource;
        $this->scopeConfig = $scopeConfig;
        $this->eavConfig = $eavConfig;
    }

    public function load(string $identifier, string $type = 'sku', ?int $storeId = null): array
    {
        $targetStoreId = $storeId !== null ? max(0, (int)$storeId) : 0;
        $identifier = trim($identifier);
        $type = strtolower(trim($type));

        if ($identifier === '') {
            throw new InputException(__('Identifier is required.'));
        }

        if (!in_array($type, ['sku', 'id'], true)) {
            throw new InputException(__('Unsupported type. Allowed values: sku, id.'));
        }

        // Step 1: minimal load to get attribute_set_id (it lives on the main entity table)
        $stub = $this->getProductByIdentifier($identifier, $type, [], $targetStoreId);
        if (!$stub || !(int)$stub->getId()) {
            throw new NoSuchEntityException(__('Product not found.'));
        }

        $productId    = (int)$stub->getId();
        $typeId       = (string)$stub->getTypeId();
        $attributeSetId = (int)$stub->getAttributeSetId();

        // Step 2: resolve which attributes to show for this set
        $attributeCodes = $this->getAttributeCodesForProduct($attributeSetId);
        $attributeMeta = $this->getAttributeMetaForCodes($attributeSetId, $attributeCodes);
        if (!empty($attributeMeta)) {
            $attributeCodes = array_values(array_map(static function (array $item): string {
                return (string)($item['attribute_code'] ?? '');
            }, $attributeMeta));
            $attributeCodes = array_values(array_filter($attributeCodes, static function (string $code): bool {
                return $code !== '';
            }));
        }

        // Step 3: reload with proper attribute codes selected
        $product = $this->getProductByIdentifier($identifier, $type, $attributeCodes, $targetStoreId);

        if (!$product || !(int)$product->getId()) {
            throw new NoSuchEntityException(__('Product not found.'));
        }

        $parentId = null;
        $children = [];

        if ($typeId === 'simple') {
            $parentId = $this->getParentIdByChildId($productId);
        }

        $msiSourceOptions = $this->getMsiSourceOptions();

        if ($typeId === 'configurable') {
            $children = $this->getChildrenByParentId($productId, $attributeCodes, $targetStoreId, $msiSourceOptions);
        }

        return [
            'id' => $productId,
            'sku' => (string)$product->getSku(),
            'type_id' => $typeId,
            'attribute_set_id' => $attributeSetId,
            'attribute_set_name' => $this->getAttributeSetNameById($attributeSetId),
            'name' => (string)$product->getData('name'),
            'image' => $this->normalizeImagePath((string)$product->getData('image')),
            'attributes' => $this->extractAttributes($product, $attributeCodes),
            'msi_sources' => $this->getMsiSourceQuantitiesBySku((string)$product->getSku()),
            'msi_source_options' => $msiSourceOptions,
            'attribute_meta' => $attributeMeta,
            'attribute_set_config' => [$attributeSetId => $attributeCodes],
            'parent_id' => $parentId,
            'children' => $children,
            'store_id' => $targetStoreId,
        ];
    }

    /**
     * Returns enabled attribute codes for a product's attribute set,
     * sourced from artm_massproducts_attr_assignment. Falls back to
     * the global artm_massproducts/api/product_load_attributes config.
     */
    private function getAttributeCodesForProduct(int $setId): array
    {
        $fromTable = $this->getEnabledCodesFromAssignmentTable($setId);
        if (!empty($fromTable)) {
            return $fromTable;
        }
        return $this->getRequestedAttributeCodes();
    }

    /**
     * Reads artm_massproducts_attr_assignment for explicitly enabled
     * attribute codes for the given set (respecting global + set rules).
     * Returns empty array when the table is absent or has no explicit records.
     */
    private function getEnabledCodesFromAssignmentTable(int $setId): array
    {
        $connection = $this->resource->getConnection();
        $assignmentTable = $connection->getTableName('artm_massproducts_attr_assignment');
        $sortOrderMap = $this->getSetSortOrderMap($setId);
        $codesInSet = array_keys($sortOrderMap);

        if (!$connection->isTableExists($assignmentTable)) {
            return [];
        }

        // Global assignments (attribute_set_id = 0)
        $globalMap = $connection->fetchPairs(
            $connection->select()
                ->from($assignmentTable, ['attribute_code', 'is_enabled'])
                ->where('attribute_set_id = ?', 0)
        );

        // Set-specific assignments
        $setMap = [];
        if ($setId > 0) {
            $setMap = $connection->fetchPairs(
                $connection->select()
                    ->from($assignmentTable, ['attribute_code', 'is_enabled'])
                    ->where('attribute_set_id = ?', $setId)
            );
        }

        $allCodes = $setId > 0 && !empty($codesInSet)
            ? $codesInSet
            : array_values(array_unique(array_merge(array_keys($globalMap), array_keys($setMap))));

        if (empty($allCodes)) {
            return [];
        }

        $result = [];
        foreach ($allCodes as $code) {
            $globalEnabled = isset($globalMap[$code]) ? (int)$globalMap[$code] === 1 : true;
            $setEnabled    = isset($setMap[$code])    ? (int)$setMap[$code] === 1    : true;
            if ($globalEnabled && ($setId === 0 || $setEnabled)) {
                $result[] = (string)$code;
            }
        }

        if (!empty($sortOrderMap)) {
            usort($result, static function (string $a, string $b) use ($sortOrderMap): int {
                $sa = (int)($sortOrderMap[$a] ?? PHP_INT_MAX);
                $sb = (int)($sortOrderMap[$b] ?? PHP_INT_MAX);
                if ($sa !== $sb) {
                    return $sa <=> $sb;
                }
                return strcmp($a, $b);
            });
        }

        return $result;
    }

    private function getSetSortOrderMap(int $setId): array
    {
        if ($setId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $entityAttributeTable = $connection->getTableName('eav_entity_attribute');
        $attributeTable = $connection->getTableName('eav_attribute');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['ea' => $entityAttributeTable], ['sort_order'])
                ->joinInner(
                    ['a' => $attributeTable],
                    'a.attribute_id = ea.attribute_id',
                    ['attribute_code']
                )
                ->where('ea.attribute_set_id = ?', $setId)
                ->order('ea.sort_order ASC')
        );

        $map = [];
        foreach ($rows as $row) {
            $code = trim((string)($row['attribute_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $map[$code] = (int)($row['sort_order'] ?? 0);
        }

        return $map;
    }

    private function getAttributeMetaForCodes(int $setId, array $attributeCodes): array
    {
        if (empty($attributeCodes)) {
            return [];
        }

        $sortOrderMap = $this->getSetSortOrderMap($setId);
        $meta = [];

        foreach ($attributeCodes as $code) {
            $code = trim((string)$code);
            if ($code === '') {
                continue;
            }

            if ($code === 'qty') {
                $meta[] = [
                    'attribute_code' => 'qty',
                    'label' => 'Qty',
                    'frontend_input' => 'number',
                    'sort_order' => (int)($sortOrderMap['qty'] ?? 99999),
                    'options' => [],
                ];
                continue;
            }

            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $code);
            $frontendInput = trim((string)($attribute ? $attribute->getFrontendInput() : ''));
            if ($frontendInput === '') {
                $frontendInput = 'text';
            }

            $label = trim((string)($attribute ? $attribute->getFrontendLabel() : ''));
            if ($label === '') {
                $label = $code;
            }

            $meta[] = [
                'attribute_code' => $code,
                'label' => $label,
                'frontend_input' => $frontendInput,
                'sort_order' => (int)($sortOrderMap[$code] ?? 99999),
                'options' => $this->getAttributeOptions($attribute),
            ];
        }

        usort($meta, static function (array $a, array $b): int {
            $sa = (int)($a['sort_order'] ?? PHP_INT_MAX);
            $sb = (int)($b['sort_order'] ?? PHP_INT_MAX);
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }
            return strcmp((string)($a['attribute_code'] ?? ''), (string)($b['attribute_code'] ?? ''));
        });

        return $meta;
    }

    private function getAttributeOptions($attribute): array
    {
        if (!$attribute || !$attribute->usesSource()) {
            return [];
        }

        $rawOptions = $attribute->getSource()->getAllOptions(false);
        if (!is_array($rawOptions)) {
            return [];
        }

        $options = [];
        foreach ($rawOptions as $item) {
            if (!is_array($item)) {
                continue;
            }

            $value = isset($item['value']) ? (string)$item['value'] : '';
            $label = isset($item['label']) ? trim((string)$item['label']) : '';

            if ($value === '' || $label === '') {
                continue;
            }

            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }

    private function getRequestedAttributeCodes(): array
    {
        $configured = (string)$this->scopeConfig->getValue(self::CONFIG_PATH_ATTRIBUTES);
        $parts = preg_split('/[\s,]+/', $configured ?: '') ?: [];
        $codes = [];

        foreach ($parts as $part) {
            $code = trim((string)$part);
            if ($code === '') {
                continue;
            }
            if (!in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        if (empty($codes)) {
            $codes = ['price', 'qty', 'color'];
        }

        return $codes;
    }

    private function getProductByIdentifier(string $identifier, string $type, array $attributeCodes, int $storeId = 0): ?Product
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId(max(0, $storeId));
        $collection->setPageSize(1)->setCurPage(1);

        // Bypass Magento's internal identity map / EAV cache so we always read fresh DB values
        $collection->setFlag('no_cache', true);

        $selectable = array_values(array_filter($attributeCodes, static function (string $code): bool {
            return $code !== 'qty';
        }));

        $mandatory = ['name', 'image'];
        $collection->addAttributeToSelect(array_values(array_unique(array_merge($mandatory, $selectable))));

        if ($type === 'id') {
            $collection->addFieldToFilter('entity_id', (int)$identifier);
        } else {
            $collection->addFieldToFilter('sku', $identifier);
        }

        $item = $collection->getFirstItem();
        if (!$item || !(int)$item->getId()) {
            return null;
        }

        return $item;
    }

    private function getParentIdByChildId(int $childId): ?int
    {
        if ($childId <= 0) {
            return null;
        }

        $connection = $this->resource->getConnection();
        $relationTable = $connection->getTableName('catalog_product_relation');

        $parentId = $connection->fetchOne(
            $connection->select()
                ->from($relationTable, ['parent_id'])
                ->where('child_id = ?', $childId)
                ->limit(1)
        );

        return $parentId ? (int)$parentId : null;
    }

    private function getChildrenByParentId(int $parentId, array $attributeCodes, int $storeId = 0, array $msiSourceOptions = []): array
    {
        if ($parentId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $relationTable = $connection->getTableName('catalog_product_relation');

        $childIds = $connection->fetchCol(
            $connection->select()
                ->from($relationTable, ['child_id'])
                ->where('parent_id = ?', $parentId)
        );

        if (empty($childIds)) {
            return [];
        }

        $allCodes = $attributeCodes;
        $entityTable = $connection->getTableName('catalog_product_entity');
        $childSetIds = $connection->fetchCol(
            $connection->select()
                ->from($entityTable, ['attribute_set_id'])
                ->where('entity_id IN (?)', array_map('intval', $childIds))
        );
        foreach (array_unique(array_map('intval', $childSetIds)) as $childSetId) {
            $allCodes = array_values(array_unique(array_merge($allCodes, $this->getAttributeCodesForProduct($childSetId))));
        }

        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId(max(0, $storeId));
        $collection->setFlag('no_cache', true);
        $collection->addFieldToFilter('entity_id', ['in' => array_map('intval', $childIds)]);

        $selectable = array_values(array_filter($allCodes, static function (string $code): bool {
            return $code !== 'qty';
        }));

        $mandatory = ['name', 'image'];
        $collection->addAttributeToSelect(array_values(array_unique(array_merge($mandatory, $selectable))));

        $children = [];
        foreach ($collection as $child) {
            $childSetId = (int)$child->getAttributeSetId();
            $childCodes = $this->getAttributeCodesForProduct($childSetId) ?: $attributeCodes;
            $childMeta = $this->getAttributeMetaForCodes($childSetId, $childCodes);
            if (!empty($childMeta)) {
                $childCodes = array_values(array_map(static function (array $item): string {
                    return (string)($item['attribute_code'] ?? '');
                }, $childMeta));
                $childCodes = array_values(array_filter($childCodes, static function (string $code): bool {
                    return $code !== '';
                }));
            }
            $children[] = [
                'id' => (int)$child->getId(),
                'sku' => (string)$child->getSku(),
                'type_id' => (string)$child->getTypeId(),
                'attribute_set_id' => $childSetId,
                'attribute_set_name' => $this->getAttributeSetNameById($childSetId),
                'name' => (string)$child->getData('name'),
                'image' => $this->normalizeImagePath((string)$child->getData('image')),
                'attributes' => $this->extractAttributes($child, $childCodes),
                'msi_sources' => $this->getMsiSourceQuantitiesBySku((string)$child->getSku()),
                'msi_source_options' => $msiSourceOptions,
                'attribute_meta' => $childMeta,
            ];
        }

        return $children;
    }

    private function extractAttributes(Product $product, array $attributeCodes): array
    {
        $data = [];
        $productId = (int)$product->getId();

        foreach ($attributeCodes as $code) {
            if ($code === 'qty') {
                $data['qty'] = $this->getQtyByProductId($productId);
                continue;
            }

            $raw = $product->getData($code);
            if ($raw === null) {
                $data[$code] = null;
                continue;
            }

            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $code);
            if ($attribute && $attribute->usesSource()) {
                $label = $attribute->getSource()->getOptionText($raw);
                $data[$code] = is_array($label) ? implode(', ', $label) : (string)$label;
                continue;
            }

            $data[$code] = $raw;
        }

        return $data;
    }

    private function getQtyByProductId(int $productId): ?float
    {
        if ($productId <= 0) {
            return null;
        }

        $connection = $this->resource->getConnection();
        $stockItem = $connection->getTableName('cataloginventory_stock_item');

        if (!$connection->isTableExists($stockItem)) {
            return null;
        }

        $qty = $connection->fetchOne(
            $connection->select()
                ->from($stockItem, ['qty'])
                ->where('product_id = ?', $productId)
                ->order('item_id DESC')
                ->limit(1)
        );

        if ($qty === false || $qty === null || $qty === '') {
            return null;
        }

        return (float)$qty;
    }

    private function getMsiSourceQuantitiesBySku(string $sku): array
    {
        $sku = trim($sku);
        if ($sku === '') {
            return [];
        }

        $connection = $this->resource->getConnection();
        $sourceItemTable = $connection->getTableName('inventory_source_item');
        $sourceTable = $connection->getTableName('inventory_source');

        if (!$connection->isTableExists($sourceItemTable)) {
            return [];
        }

        $select = $connection->select()
            ->from(['si' => $sourceItemTable], ['source_code', 'quantity'])
            ->where('si.sku = ?', $sku)
            ->order('si.source_code ASC');

        if ($connection->isTableExists($sourceTable)) {
            $select->joinLeft(['s' => $sourceTable], 's.source_code = si.source_code', ['source_name' => 'name']);
        }

        $rows = $connection->fetchAll($select);
        $out = [];
        foreach ($rows as $row) {
            $code = trim((string)($row['source_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $out[] = [
                'source_code' => $code,
                'source_name' => trim((string)($row['source_name'] ?? '')),
                'qty' => isset($row['quantity']) ? (float)$row['quantity'] : 0.0,
            ];
        }

        return $out;
    }

    private function getMsiSourceOptions(): array
    {
        $connection = $this->resource->getConnection();
        $sourceTable = $connection->getTableName('inventory_source');

        if (!$connection->isTableExists($sourceTable)) {
            return [];
        }

        $select = $connection->select()
            ->from($sourceTable, ['source_code', 'name'])
            ->where('source_code IS NOT NULL')
            ->order('source_code ASC');

        $rows = $connection->fetchAll($select);
        $out = [];
        foreach ($rows as $row) {
            $code = trim((string)($row['source_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $out[] = [
                'source_code' => $code,
                'source_name' => trim((string)($row['name'] ?? $code)),
            ];
        }

        return $out;
    }

    private function getAttributeSetNameById(int $attributeSetId): string
    {
        if ($attributeSetId <= 0) {
            return '';
        }

        $connection = $this->resource->getConnection();
        $setTable = $connection->getTableName('eav_attribute_set');

        if (!$connection->isTableExists($setTable)) {
            return '';
        }

        $name = $connection->fetchOne(
            $connection->select()
                ->from($setTable, ['attribute_set_name'])
                ->where('attribute_set_id = ?', $attributeSetId)
                ->limit(1)
        );

        return $name ? (string)$name : '';
    }

    private function normalizeImagePath(string $image): string
    {
        $image = trim($image);
        if ($image === '' || $image === 'no_selection') {
            return '';
        }

        return '/media/catalog/product' . (strpos($image, '/') === 0 ? $image : '/' . $image);
    }
}
