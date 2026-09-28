<?php
declare(strict_types=1);

namespace ArtM\MassProducts\Model\Api;

use ArtM\MassProducts\Api\ProductSaveInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductSave implements ProductSaveInterface
{
    private ProductRepositoryInterface $productRepository;
    private ProductResource $productResource;
    private ResourceConnection $resource;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductResource $productResource
     * @param ResourceConnection $resource
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductResource $productResource,
        ResourceConnection $resource
    ) {
        $this->productRepository = $productRepository;
        $this->productResource = $productResource;
        $this->resource = $resource;
    }

    /**
     * Persist the provided product attribute changes.
     *
     * @param string $sku
     * @param array $attributes
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function save(string $sku, array $attributes = [], ?int $storeId = null): array
    {
        $targetStoreId = $storeId !== null ? max(0, (int)$storeId) : 0;
        $sku = trim($sku);
        if ($sku === '') {
            throw new InputException(__('SKU is required.'));
        }

        if (empty($attributes)) {
            throw new InputException(__('At least one attribute is required.'));
        }

        try {
            $product = $this->productRepository->get($sku, true, $targetStoreId, true);
            $product->setStoreId($targetStoreId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Product not found.'));
        }

        $typeId = (string)$product->getTypeId();
        $requiredMap = $this->getRequiredAttributeMap((int)$product->getAttributeSetId(), $typeId);
        $sourceQtyMap = $this->extractMsiSourceQtyMap($attributes);
        $hasLegacyQty = array_key_exists('qty', $attributes);
        $legacyQty = $hasLegacyQty ? $this->normalizeNumericValue($attributes['qty'], 'qty') : null;
        $updatedAttributeCodes = [];

        foreach ($attributes as $code => $value) {
            $attributeCode = trim((string)$code);
            if ($attributeCode === '' || $attributeCode === 'qty' || strpos($attributeCode, 'msi_qty__') === 0) {
                continue;
            }

            $attribute = $product->getResource()->getAttribute($attributeCode);
            if (!$attribute || !(int)$attribute->getId()) {
                throw new InputException(__('Unsupported attribute: %1', $attributeCode));
            }

            $normalizedValue = $this->normalizeAttributeValue($value, $attributeCode, $attribute);
            if (!empty($requiredMap[$attributeCode]) && $this->isEmptyRequiredValue($normalizedValue)) {
                throw new LocalizedException(__('Required attribute "%1" cannot be empty.', $attribute->getFrontendLabel() ?: $attributeCode));
            }

            $product->setData($attributeCode, $normalizedValue);
            $updatedAttributeCodes[] = $attributeCode;
        }

        if (!empty($updatedAttributeCodes)) {
            $this->productRepository->save($product);
        }

        if (!empty($sourceQtyMap)) {
            $this->saveMsiSourceItems($sku, $sourceQtyMap);
        } elseif ($hasLegacyQty && $legacyQty !== null) {
            $this->updateLegacyQtyOnly($sku, $legacyQty);
        }

        return [
            'success' => true,
            'message' => 'Product updated',
            'updated_attributes' => $updatedAttributeCodes,
            'store_id' => $targetStoreId,
        ];
    }

    /**
     * Normalize incoming attribute value according to the attribute input type.
     *
     * @param mixed $value
     * @param string $attributeCode
     * @param mixed $attribute
     * @return mixed
     * @throws InputException
     */
    private function normalizeAttributeValue($value, string $attributeCode, $attribute)
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        $frontendInput = (string)$attribute->getFrontendInput();
        if ($frontendInput === 'price' || $frontendInput === 'weight') {
            return $this->normalizeNumericValue($value, $attributeCode);
        }

        if ($frontendInput === 'boolean' || $frontendInput === 'select') {
            return $value === '' ? null : (string)$value;
        }

        if ($frontendInput === 'multiselect') {
            if (is_array($value)) {
                return implode(',', array_map('strval', $value));
            }
            return $value === '' ? '' : (string)$value;
        }

        return $value;
    }

    /**
     * Normalize a numeric field value.
     *
     * @param mixed $value
     * @param string $attributeCode
     * @return float
     * @throws InputException
     */
    private function normalizeNumericValue($value, string $attributeCode): float
    {
        $stringValue = trim((string)$value);
        if ($stringValue === '' || !is_numeric($stringValue)) {
            throw new InputException(__('Attribute "%1" requires a numeric value.', $attributeCode));
        }

        return (float)$stringValue;
    }

    /**
     * Determine whether a required value should be treated as empty.
     *
     * @param mixed $value
     * @return bool
     */
    private function isEmptyRequiredValue($value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        if (is_array($value) && empty($value)) {
            return true;
        }
        return false;
    }

    /**
     * Collect required attributes for the product attribute set and type.
     *
     * @param int $attributeSetId
     * @param string $typeId
     * @return array
     */
    private function getRequiredAttributeMap(int $attributeSetId, string $typeId): array
    {
        if ($attributeSetId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $eavAttrTable = $connection->getTableName('eav_attribute');
        $eavEntityAttrTable = $connection->getTableName('eav_entity_attribute');
        $catalogEavAttrTable = $connection->getTableName('catalog_eav_attribute');
        $hasCatalogEav = $connection->isTableExists($catalogEavAttrTable);

        $select = $connection->select()
            ->from(['a' => $eavAttrTable], ['attribute_code'])
            ->joinInner(['ea' => $eavEntityAttrTable], 'ea.attribute_id = a.attribute_id', [])
            ->where('ea.attribute_set_id = ?', $attributeSetId)
            ->where('a.is_required = ?', 1);

        if ($hasCatalogEav) {
            $select->joinLeft(
                ['cea' => $catalogEavAttrTable],
                'cea.attribute_id = a.attribute_id',
                ['apply_to' => 'apply_to', 'is_visible' => 'is_visible']
            );
            $select->where('COALESCE(cea.is_visible, 1) = ?', 1);
        }

        $rows = $connection->fetchAll($select);
        $systemAttributes = [
            'dynamic_price', 'dynamic_sku', 'dynamic_weight', 'price_view', 'shipment_type',
            'ship_bundle_items', 'giftcard_amounts', 'links_purchased_separately', 'links_title',
            'samples_title', 'created_at', 'updated_at'
        ];

        $map = [];
        foreach ($rows as $row) {
            $code = trim((string)($row['attribute_code'] ?? ''));
            $applyTo = (string)($row['apply_to'] ?? '');
            if ($code === '' || in_array($code, $systemAttributes, true)) {
                continue;
            }
            if ($applyTo !== '' && strpos($applyTo, $typeId) === false) {
                continue;
            }
            $map[$code] = true;
        }

        return $map;
    }

    /**
     * Extract MSI source quantity changes from the provided attributes payload.
     *
     * @param array $attributes
     * @return array
     * @throws InputException
     */
    private function extractMsiSourceQtyMap(array &$attributes): array
    {
        $out = [];
        foreach ($attributes as $code => $value) {
            if (strpos((string)$code, 'msi_qty__') !== 0) {
                continue;
            }

            $sourceCode = substr((string)$code, 9);
            unset($attributes[$code]);
            if ($sourceCode === '') {
                continue;
            }

            $qtyRaw = trim((string)$value);
            if ($qtyRaw === '' || !is_numeric($qtyRaw)) {
                throw new InputException(__('MSI source quantity for "%1" must be numeric.', $sourceCode));
            }

            $out[$sourceCode] = (float)$qtyRaw;
        }

        return $out;
    }

    /**
     * Save source item quantities through MSI APIs when available.
     *
     * @param string $sku
     * @param array $sourceQtyMap
     * @return void
     */
    private function saveMsiSourceItems(string $sku, array $sourceQtyMap): void
    {
        if ($sku === '' || empty($sourceQtyMap)) {
            return;
        }

        $sourceItemsSaveClass = 'Magento\\InventoryApi\\Api\\SourceItemsSaveInterface';
        $sourceItemFactoryClass = 'Magento\\InventoryApi\\Api\\Data\\SourceItemInterfaceFactory';
        if (!interface_exists($sourceItemsSaveClass) || !class_exists($sourceItemFactoryClass)) {
            return;
        }

        $om = ObjectManager::getInstance();
        $sourceItemsSave = $om->get($sourceItemsSaveClass);
        $sourceItemFactory = $om->get($sourceItemFactoryClass);

        $items = [];
        foreach ($sourceQtyMap as $sourceCode => $qty) {
            $source = trim((string)$sourceCode);
            if ($source === '') {
                continue;
            }

            $item = $sourceItemFactory->create();
            $item->setSourceCode($source);
            $item->setSku($sku);
            $item->setQuantity((float)$qty);
            $item->setStatus(((float)$qty) > 0 ? 1 : 0);
            $items[] = $item;
        }

        if (!empty($items)) {
            $sourceItemsSave->execute($items);
        }
    }

    /**
     * Update the legacy stock item directly for a SKU.
     *
     * @param string $sku
     * @param float $qty
     * @return void
     */
    private function updateLegacyQtyOnly(string $sku, float $qty): void
    {
        $stockRegistryClass = 'Magento\\CatalogInventory\\Api\\StockRegistryInterface';
        if (!interface_exists($stockRegistryClass)) {
            return;
        }

        $om = ObjectManager::getInstance();
        $stockRegistry = $om->get($stockRegistryClass);
        $stockItem = $stockRegistry->getStockItemBySku($sku);
        if (!$stockItem) {
            return;
        }

        $stockItem->setQty($qty);
        $stockItem->setIsInStock($qty > 0 ? 1 : 0);
        $stockItem->setManageStock(1);
        $stockRegistry->updateStockItemBySku($sku, $stockItem);
    }
}