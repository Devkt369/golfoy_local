<?php
namespace WeserveCommerce\AjaxSearch\Model\Indexer;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class DocumentBuilder
{
    private $categoryCollectionFactory;

    public function __construct(CategoryCollectionFactory $categoryCollectionFactory)
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Preloads all unique attribute set names in a single database query safely.
     */
    public function loadAttributeSetMap($collection): array
    {
        try {
            $connection = $collection->getConnection();
            $table = $collection->getResource()->getTable('eav_attribute_set');
            
            $select = $connection->select()
                ->from($table, ['attribute_set_id', 'attribute_set_name']);
            
            return $connection->fetchPairs($select);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function buildDocument($product, int $storeId, array $attributeSetMap, array $configAttributes): array
    {
        $productId = (int)$product->getId();
        $attributeSetId = (int)$product->getAttributeSetId();
        $attributeSetName = $attributeSetMap[$attributeSetId] ?? '';

        $rawCategories = $product->getCategoryIds();
        $categoryIds = is_array($rawCategories) ? array_filter(array_map('intval', $rawCategories)) : [];

        $categoryPaths = $this->getCategoryPaths($categoryIds);

        $document = [
            '_id' => $productId . '_' . $storeId,
            'id' => (string)$productId,
            'sku' => (string)($product->getSku() ?? ''),
            'name' => (string)($product->getName() ?? ''),
            'attribute_set_name' => (string)$attributeSetName,
            'url_key' => (string)($product->getUrlKey() ?? ''),
            'store_id' => $storeId,
            'price' => (float)($product->getFinalPrice() ?? 0.0),
            'special_price' => $product->getSpecialPrice() !== null ? (float)$product->getSpecialPrice() : null,
            'category_paths' => $categoryPaths,            
            'status' => (int)($product->getStatus() ?? 0),
            'visibility' => (int)($product->getVisibility() ?? 0),
            'image' => $this->toStringValue($product->getData('image')),
            'small_image' => $this->toStringValue($product->getData('small_image')),
            'thumbnail' => $this->toStringValue($product->getData('thumbnail'))
        ];

        foreach (['description', 'short_description', 'brand'] as $field) {
            $document[$field] = $this->resolveAttributeDisplayValue($product, $field);
        }

        $configAttributes = is_array($configAttributes) ? array_filter($configAttributes, function ($code) {
            return !empty($code) && is_string($code);
        }) : [];

        foreach ($configAttributes as $attributeCode) {
            if (array_key_exists($attributeCode, $document)) {
                continue;
            }
            $document[$attributeCode] = $this->resolveAttributeDisplayValue($product, $attributeCode);
        }

        return $document;
    }

    private function getCategoryPaths(array $categoryIds): array
    {
        $categoryPaths = [];

        if (empty($categoryIds)) {
            return $categoryPaths;
        }

        try {
            $categoryCollection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect(['name', 'parent_id'])
                ->addFieldToFilter('entity_id', ['in' => $categoryIds]);

            foreach ($categoryCollection as $category) {
                $path = $category->getPath();
                if (!$path) {
                    continue;
                }

                $pathIds = array_filter(array_map('intval', explode('/', (string)$path)));
                if (empty($pathIds)) {
                    continue;
                }

                $pathCollection = $this->categoryCollectionFactory->create()
                    ->addAttributeToSelect('name')
                    ->addFieldToFilter('entity_id', ['in' => $pathIds]);

                $names = [];
                foreach ($pathCollection as $pathCategory) {
                    $catName = $pathCategory->getName();
                    if ($catName !== null && $catName !== '') {
                        $names[] = (string)$catName;
                    }
                }

                if (!empty($names)) {
                    $categoryPaths[] = implode(' > ', $names);
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return array_values(array_unique($categoryPaths));
    }

    private function resolveAttributeDisplayValue($product, string $attributeCode)
    {
        try {
            $text = $product->getAttributeText($attributeCode);

            if (is_array($text)) {
                return array_values(array_map('strval', array_filter($text)));
            }

            if ($text !== false && $text !== null && $text !== '') {
                return (string)$text;
            }

            $value = $product->getData($attributeCode);

            if ($value === false || $value === null || $value === '') {
                return null;
            }

            if (is_array($value)) {
                return array_values(array_map('strval', array_filter($value)));
            }

            return (string)$value;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toStringValue($value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map('strval', array_filter($value)));
        }

        return (string)($value ?? '');
    }
}