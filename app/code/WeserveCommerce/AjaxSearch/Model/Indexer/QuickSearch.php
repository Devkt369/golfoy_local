<?php
namespace WeserveCommerce\AjaxSearch\Model\Indexer;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use WeserveCommerce\AjaxSearch\Model\Config;

class QuickSearch implements IndexerActionInterface, MviewActionInterface
{
    /**
     * Configurable products are matched by type_id string, not a class
     * constant, so this module doesn't need a hard dependency on the
     * Magento_ConfigurableProduct module just for one constant.
     */
    private const CONFIGURABLE_TYPE_ID = 'configurable';

    /**
     * Attributes that belong to the product itself and should NOT be
     * rolled up from child variants - a configurable's name/sku/price/etc.
     * should never be overwritten by whatever a child happens to have.
     */
    private const ROLLUP_EXCLUDED_ATTRIBUTES = ['name', 'sku', 'description', 'short_description', 'brand', 'price', 'url_key'];

    private $config;
    private $collectionFactory;
    private $categoryCollectionFactory;
    private $productFactory;
    private $storeManager;
    private $curlFactory;
    private $logger;
    private $resourceConnection;

    public function __construct(
        Config $config,
        CollectionFactory $collectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        CurlFactory $curlFactory,
        LoggerInterface $logger,
        ResourceConnection $resourceConnection
    ) {
        $this->config = $config;
        $this->collectionFactory = $collectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    public function executeFull()
    {
        foreach ($this->storeManager->getStores(false) as $store) {
            $storeId = (int)$store->getId();
            if (!$this->config->isEnabled($storeId)) {
                continue;
            }

            $this->createIndex($storeId);
            $this->rebuildStore($storeId);
        }
    }

    public function executeList(array $ids)
    {
        $this->indexProducts($ids);
    }

    public function execute($ids)
    {
        $this->indexProducts((array)$ids);
    }

    public function executeRow($id)
    {
        // die('executeRow is not implemented. Use executeList instead.');
        $this->indexProducts([(int)$id]);
    }

    private function rebuildStore(int $storeId): void
    {
        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addAttributeToSelect($this->config->getIndexedAttributes($storeId));
        $collection->addAttributeToSelect(['url_key','image','small_image','thumbnail','visibility']);
        $this->sendCollection($collection, $storeId);
    }

    private function createIndex(int $storeId): void
    {
        $indexName = $this->config->getQuickSearchIndexName($storeId);
        $this->request('DELETE', '/' . rawurlencode($indexName));
        $this->request('PUT', '/' . rawurlencode($indexName), [
            'settings' => ['number_of_shards' => 1, 'number_of_replicas' => 0],
            'mappings' => ['properties' => $this->buildMappingProperties($storeId)]
        ]);
    }

    /**
     * Every field the indexer can ever write - the fixed catalog fields
     * plus every attribute in fastsearch/indexing/attributes - gets an
     * explicit mapping here. Without this, OpenSearch falls back to
     * dynamic mapping and infers each field's type from whichever product
     * happens to be indexed first, which is how a select attribute like
     * "shoe_size" can end up mapped inconsistently depending on indexing
     * order across very different product types (clubs, shoes, apparel,
     * accessories) sharing one index.
     *
     * Configured attributes default to text + a .keyword sub-field, since
     * most of them are select/multiselect option labels (e.g. "color":
     * "Red") - text gives you full-text/fuzzy matching, .keyword gives you
     * exact filtering and aggregations (facets) on the same field.
     */
    private function buildMappingProperties(int $storeId): array
    {
        $properties = [
            'id' => ['type' => 'keyword'],
            'sku' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
            'name' => ['type' => 'text'],
            'attribute_set_name' => ['type' => 'text'],
            'store_id' => ['type' => 'integer'],
            'status' => ['type' => 'integer'],
            'visibility' => ['type' => 'integer'],
            'price' => ['type' => 'double'],
            'special_price' => ['type' => 'double'],
            'category_ids' => ['type' => 'integer'],
            'category_paths' => [
                'type' => 'text',
                'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 512]]
            ],
            'url_key' => ['type' => 'keyword'],
            'image' => ['type' => 'keyword'],
            'small_image' => ['type' => 'keyword'],
            'thumbnail' => ['type' => 'keyword'],
            'description' => ['type' => 'text'],
            'short_description' => ['type' => 'text'],
            'brand' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]]
        ];

        foreach ($this->config->getIndexedAttributes($storeId) as $attributeCode) {
            if (isset($properties[$attributeCode])) {
                continue;
            }

            $properties[$attributeCode] = [
                'type' => 'text',
                'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
            ];
        }

        return $properties;
    }

    private function indexProducts(array $ids): void
    {
        if (!$ids) {
            return;
        }

        // If a saved product is a configurable's child (e.g. someone
        // updates the "shirt_size" of one variant), the PARENT's document
        // - which is what actually carries the rolled-up size/color list -
        // needs to be rebuilt too, not just the child.
        $ids = $this->expandIdsWithConfigurableParents($ids);

        foreach ($this->storeManager->getStores(true) as $store) {
            $storeId = (int)$store->getId();
            if (!$this->config->isEnabled($storeId)) {
                continue;
            }

            $collection = $this->collectionFactory->create();
            $collection->setStoreId($storeId);
            $collection->addStoreFilter($storeId);
            $collection->addIdFilter($ids);
            $collection->addAttributeToSelect($this->config->getIndexedAttributes($storeId));
            $collection->addAttributeToSelect(['visibility']);
            $this->sendCollection($collection, $storeId);
        }
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function expandIdsWithConfigurableParents(array $ids): array
    {
        $ids = $this->normalizeIds($ids);
        if (!$ids) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        if (!$connection) {
            return $ids;
        }

        $table = $this->resourceConnection->getTableName('catalog_product_super_link');
        if ($table === null || $table === '') {
            return $ids;
        }

        $select = $connection->select()
            ->from($table, ['parent_id'])
            ->where('product_id IN (?)', $ids)
            ->distinct();

        $parentIds = array_map('intval', $connection->fetchCol($select));

        return array_values(array_unique(array_merge($ids, $parentIds)));
    }

    private function sendCollection($collection, int $storeId): void
    {
        $pageSize = 200;
        $page = 1;

        do {
            $collection->clear();
            $collection->setPageSize($pageSize);
            $collection->setCurPage($page);

            $productsOnPage = [];
            foreach ($collection as $product) {
                $productsOnPage[] = $product;
            }

            $processed = count($productsOnPage);
            if ($processed === 0) {
                break;
            }

            $productIdsOnPage = array_map(static function ($product) {
                return (int)$product->getId();
            }, $productsOnPage);

            $configurableIdsOnPage = [];
            foreach ($productsOnPage as $product) {
                if ($product->getTypeId() === self::CONFIGURABLE_TYPE_ID) {
                    $configurableIdsOnPage[] = (int)$product->getId();
                }
            }

            // parent_id => [child_id, ...] for configurables on this page
            $parentToChildIds = $this->getChildProductIds($configurableIdsOnPage);
            $categoryPathsByProduct = $this->getCategoryPathsByProducts($productsOnPage, $storeId);

            // For every product ON THIS PAGE, is it a child of SOME
            // configurable (whether or not that parent is on this page)?
            // Those get skipped as standalone documents if hidden.
            $childOfSomeParentIds = $this->getChildProductIds([], $productIdsOnPage);

            // attrCode -> [unique values] per parent, gathered from all its children
            $rollupByParent = $this->buildConfigurableRollup($parentToChildIds, $storeId);

            $lines = [];

            foreach ($productsOnPage as $product) {
                $productId = (int)$product->getId();

                $isHiddenChild = in_array($productId, $childOfSomeParentIds, true)
                    && (int)$product->getVisibility() === Visibility::VISIBILITY_NOT_VISIBLE;

                if ($isHiddenChild) {
                    // Its attribute values were already folded into the
                    // parent's document via $rollupByParent - it doesn't
                    // need (and shouldn't have) its own search result.
                    continue;
                }

                $document = $this->buildDocument(
                    $product,
                    $storeId,
                    $rollupByParent[$productId] ?? [],
                    $categoryPathsByProduct[$productId] ?? []
                );
                $lines[] = json_encode(['index' => ['_index' => $this->config->getQuickSearchIndexName($storeId), '_id' => $document['_id']]]);
                unset($document['_id']);
                $lines[] = json_encode($document);

                if (count($lines) >= 400) {
                    $this->sendBulk($lines);
                    $lines = [];
                }
            }

            if ($lines) {
                $this->sendBulk($lines);
            }

            if ($processed < $pageSize) {
                break;
            }

            $page++;
        } while (true);
    }

    /**
     * Bulk-reads catalog_product_super_link in ONE query (instead of one
     * query per configurable) to build a parent_id => [child_id,...] map.
     * Pass $parentIds to look up children of specific configurables, or
     * $childProductIds to instead find which of those specific product ids
     * are a child of any parent (used for the "hide this child" check) -
     * in that mode the return value is a flat list of child ids, not a map.
     *
     * @param int[] $parentIds
     * @param int[] $childProductIds
     * @return array
     */
    private function getChildProductIds(array $parentIds, array $childProductIds = [])
    {
        $connection = $this->resourceConnection->getConnection();
        if (!$connection) {
            return [];
        }

        $table = $this->resourceConnection->getTableName('catalog_product_super_link');
        if ($table === null || $table === '') {
            return [];
        }

        $normalizedChildProductIds = $this->normalizeIds($childProductIds);
        if ($normalizedChildProductIds) {
            $select = $connection->select()
                ->from($table, ['product_id'])
                ->where('product_id IN (?)', $normalizedChildProductIds)
                ->distinct();

            return array_map('intval', $connection->fetchCol($select));
        }

        $normalizedParentIds = $this->normalizeIds($parentIds);
        if (!$normalizedParentIds) {
            return [];
        }

        $select = $connection->select()
            ->from($table, ['parent_id', 'product_id'])
            ->where('parent_id IN (?)', $normalizedParentIds);

        $map = [];
        foreach ($connection->fetchAll($select) as $row) {
            $parentId = (int)($row['parent_id'] ?? 0);
            $productId = (int)($row['product_id'] ?? 0);

            if ($parentId <= 0 || $productId <= 0) {
                continue;
            }

            $map[$parentId][] = $productId;
        }

        return $map;
    }

    /**
     * For every configurable in $parentToChildIds, loads each child and
     * collects the DISTINCT values of every rollup-eligible attribute
     * across all its children - e.g. a t-shirt with S/M/L/XL children each
     * carrying a different shirt_size ends up with
     * ['shirt_size' => ['S','M','L','XL']] here, keyed by parent id, ready
     * to merge into the parent's own document in buildDocument().
     *
     * @return array<int, array<string, string[]>>
     */
    private function buildConfigurableRollup(array $parentToChildIds, int $storeId): array
    {
        if (!$parentToChildIds) {
            return [];
        }

        $attributeCodes = array_filter(array_map(function ($attributeCode) {
            $attributeCode = trim((string)$attributeCode);
            return $attributeCode !== '' ? $attributeCode : null;
        }, array_diff($this->config->getIndexedAttributes($storeId), self::ROLLUP_EXCLUDED_ATTRIBUTES)));

        $rollup = [];

        foreach ($parentToChildIds as $parentId => $childIds) {
            $valueSets = [];

            foreach ($childIds as $childId) {
                $childId = (int)$childId;
                if ($childId <= 0) {
                    continue;
                }

                $childProductModel = $this->productFactory->create()->setStoreId($storeId)->load($childId);

                foreach ($attributeCodes as $attributeCode) {
                    $value = $this->resolveAttributeDisplayValue($childProductModel, $childId, $attributeCode, $storeId);

                    if ($value === null) {
                        continue;
                    }

                    foreach ((array)$value as $singleValue) {
                        if ($singleValue !== '') {
                            $valueSets[$attributeCode][$singleValue] = true;
                        }
                    }
                }
            }

            $rollup[$parentId] = array_map('array_keys', $valueSets);
        }

        return $rollup;
    }

    /**
     * @param array<string, string[]> $rollupValues attrCode => distinct child values, from buildConfigurableRollup()
     */
    private function getCategoryPathsByProducts(array $products, int $storeId): array
    {
        $productCategoryIds = [];
        $categoryIds = [];

        foreach ($products as $product) {
            $productId = (int)$product->getId();
            $productCategoryIds[$productId] = array_values(array_filter(array_map(
                'intval',
                (array)$product->getCategoryIds()
            )));
            $categoryIds = array_merge($categoryIds, $productCategoryIds[$productId]);
        }

        $categoryIds = array_values(array_unique($categoryIds));
        if (!$categoryIds) {
            return [];
        }

        try {
            $assignedCategories = $this->categoryCollectionFactory->create();
            $assignedCategories->setStoreId($storeId);
            $assignedCategories->addAttributeToSelect('name');
            $assignedCategories->addFieldToFilter('entity_id', ['in' => $categoryIds]);

            $pathsByCategory = [];
            $ancestorIds = [];
            foreach ($assignedCategories as $category) {
                $pathIds = array_values(array_filter(array_map(
                    'intval',
                    explode('/', (string)$category->getPath())
                )));
                $pathsByCategory[(int)$category->getId()] = $pathIds;
                $ancestorIds = array_merge($ancestorIds, $pathIds);
            }

            $ancestorIds = array_values(array_unique($ancestorIds));
            if (!$ancestorIds) {
                return [];
            }

            $ancestorCategories = $this->categoryCollectionFactory->create();
            $ancestorCategories->setStoreId($storeId);
            $ancestorCategories->addAttributeToSelect('name');
            $ancestorCategories->addFieldToFilter('entity_id', ['in' => $ancestorIds]);

            $namesByCategory = [];
            foreach ($ancestorCategories as $category) {
                $namesByCategory[(int)$category->getId()] = (string)$category->getName();
            }

            $pathsByProduct = [];
            foreach ($productCategoryIds as $productId => $assignedIds) {
                $paths = [];
                foreach ($assignedIds as $categoryId) {
                    $pathIds = $pathsByCategory[$categoryId] ?? [];
                    $pathIds = array_slice($pathIds, 2);

                    $names = [];
                    foreach ($pathIds as $pathId) {
                        if (!empty($namesByCategory[$pathId])) {
                            $names[] = $namesByCategory[$pathId];
                        }
                    }

                    if ($names) {
                        $paths[] = implode(' > ', $names);
                    }
                }

                $pathsByProduct[$productId] = array_values(array_unique($paths));
            }

            return $pathsByProduct;
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to resolve product category breadcrumbs: ' . $exception->getMessage());
            return [];
        }
    }

    private function buildDocument($product, int $storeId, array $rollupValues = [], array $categoryPaths = []): array
    {
        $productId = (int)$product->getId();
        $productModel = $this->productFactory->create()->setStoreId($storeId)->load($productId);
        $price = (float)$productModel->getPrice();
        if ($price <= 0) {
            $price = (float)$productModel->getFinalPrice();
        }

        $document = [
            '_id' => $productId . '_' . $storeId,
            'id' => (string)$productId,
            'sku' => (string)$productModel->getSku(),
            'name' => (string)$productModel->getName(),
            'attribute_set_name' => $this->getAttributeSetName($productModel),
            'url_key' => (string)$productModel->getUrlKey(),
            'store_id' => $storeId,
            'price' => $price,
            'special_price' => $productModel->getSpecialPrice() !== null ? (float)$productModel->getSpecialPrice() : null,
            'category_ids' => array_map('strval', (array)$productModel->getCategoryIds()),
            'category_paths' => $categoryPaths,
            'status' => (int)$this->getProductAttributeValue($productModel, $productId, 'status', $storeId),
            'visibility' => (int)$this->getProductAttributeValue($productModel, $productId, 'visibility', $storeId),
            'image' => $this->toStringValue($this->getProductAttributeValue($productModel, $productId, 'image', $storeId)),
            'small_image' => $this->toStringValue($this->getProductAttributeValue($productModel, $productId, 'small_image', $storeId)),
            'thumbnail' => $this->toStringValue($this->getProductAttributeValue($productModel, $productId, 'thumbnail', $storeId))
        ];

        // Baseline text fields: always present in the document, explicitly
        // null when the product's attribute set doesn't carry them (e.g. a
        // product with no brand set) rather than omitting the key.
        foreach (['description', 'short_description', 'brand'] as $field) {
            $document[$field] = $this->resolveAttributeDisplayValue($productModel, $productId, $field, $storeId);
        }

        // Every configured indexed attribute gets
        // the same treatment: stored explicitly, with null (not omitted)
        // when this product's attribute set doesn't include it. A shoe
        // product will still have a "shirt_size" key set to null instead of
        // missing entirely - so every document in the index has the exact
        // same shape, which is what makes filtering/faceting and relevance
        // scoring behave predictably across very different product types
        // (golf clubs, shoes, apparel, accessories) sharing one index.
        foreach ($this->config->getIndexedAttributes($storeId) as $attributeCode) {
            if (array_key_exists($attributeCode, $document)) {
                continue;
            }

            $ownValue = $this->resolveAttributeDisplayValue($productModel, $productId, $attributeCode, $storeId);
            $document[$attributeCode] = $this->mergeAttributeValues($ownValue, $rollupValues[$attributeCode] ?? []);
        }

        // print_r($document);die;

        return $document;
    }

    /**
     * Uses the product's own value when set; child values are a fallback
     * for configurable variant attributes that are not set on the parent.
     *
     * @param string|string[]|null $ownValue
     * @param string[] $rolledUpValues
     * @return string|string[]|null
     */
    private function mergeAttributeValues($ownValue, array $rolledUpValues)
    {
        if ($ownValue !== null && $ownValue !== '' && $ownValue !== []) {
            return $ownValue;
        }

        $all = [];
        foreach ($rolledUpValues as $value) {
            if ($value !== '') {
                $all[$value] = true;
            }
        }

        if (!$all) {
            return null;
        }

        $unique = array_keys($all);

        return count($unique) === 1 ? $unique[0] : $unique;
    }

    /**
     * Resolves a single attribute to the value that should be stored in the
     * index:
     *  - select / dropdown / swatch (single-value): the option label string
     *  - multiselect (incl. multi-value swatches): a real array of option
     *    label strings - NOT joined into one string. OpenSearch indexes an
     *    array under a normal field mapping natively, so e.g. a "color"
     *    multiselect with ["Red","Blue"] lets you term-filter on
     *    color.keyword = "Red" and still match. Joining into "Red, Blue"
     *    would make that filter silently fail.
     *  - plain text: the raw value
     *  - missing on this product's attribute set: explicitly null
     *
     * @return string|string[]|null
     */
    private function resolveAttributeDisplayValue($productModel, int $productId, string $attributeCode, int $storeId)
    {
        $attributeCode = $this->normalizeAttributeCode($attributeCode);
        if ($attributeCode === null || $productId <= 0) {
            return null;
        }

        $value = $this->getProductAttributeValue($productModel, $productId, $attributeCode, $storeId);

        if ($value === null) {
            return null;
        }

        $text = $productModel->getAttributeText($attributeCode);

        if (is_array($text)) {
            return array_values(array_map('strval', $text));
        }

        if ($text !== false && $text !== null && $text !== '') {
            return (string)$text;
        }

        if (is_array($value)) {
            return array_values(array_map('strval', $value));
        }

        return (string)$value;
    }

    private function getProductAttributeValue($productModel, int $productId, string $attributeCode, int $storeId)
    {
        $attributeCode = $this->normalizeAttributeCode($attributeCode);
        if ($attributeCode === null || $productId <= 0) {
            return null;
        }

        $value = $productModel->getResource()->getAttributeRawValue($productId, $attributeCode, $storeId);

        if ($value === false || $value === null || $value === '') {
            return null;
        }

        return $value;
    }

    private function normalizeIds(array $ids): array
    {
        $filtered = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        })));

        return $filtered;
    }

    private function normalizeAttributeCode($attributeCode): ?string
    {
        if (!is_string($attributeCode) && !is_numeric($attributeCode)) {
            return null;
        }

        $attributeCode = trim((string)$attributeCode);
        return $attributeCode === '' ? null : $attributeCode;
    }

    private function toStringValue($value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }

        return (string)($value ?? '');
    }

    private function getAttributeSetName($productModel): string
    {
        $attributeSetId = (int)$productModel->getAttributeSetId();
        if ($attributeSetId <= 0) {
            return '';
        }

        try {
            $connection = $productModel->getResource()->getConnection();
            $select = $connection->select()
                ->from($productModel->getResource()->getTable('eav_attribute_set'), ['attribute_set_name'])
                ->where('attribute_set_id = ?', $attributeSetId)
                ->limit(1);

            return (string)$connection->fetchOne($select);
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to load product attribute set: ' . $exception->getMessage());
            return '';
        }
    }

    private function sendBulk(array $lines): void
    {
        $body = implode("\n", $lines) . "\n";
        $this->request('POST', '/_bulk', $body, 'application/x-ndjson');
    }

    private function request(string $method, string $path, $payload = null, string $contentType = 'application/json')
    {
        try {
            $curl = $this->curlFactory->create();
            $curl->setTimeout($this->config->getTimeout(0));
            $curl->addHeader('Content-Type', $contentType);
            $url = rtrim($this->config->getServerBaseUrl(0), '/') . $path;
            if ($this->config->isAuthEnabled(0)) {
                $curl->setCredentials($this->config->getUsername(0), $this->config->getPassword(0));
            }
            if ($method === 'DELETE') {
                $curl->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
                $curl->post($url, '');
            } elseif ($method === 'PUT') {
                $curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                $curl->post($url, json_encode($payload));
            } else {
                $curl->post($url, $payload);
            }
            $status = (int)$curl->getStatus();
            if ($status >= 400 && $status !== 404) {
                throw new \RuntimeException(sprintf('OpenSearch HTTP %d: %s', $status, $curl->getBody()));
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Quick search index request failed: ' . $exception->getMessage());
            throw $exception;
        }
    }
}
