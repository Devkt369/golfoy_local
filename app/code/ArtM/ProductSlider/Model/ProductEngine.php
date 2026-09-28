<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

class ProductEngine
{
    /** @var array<int, array> */
    private array $debugTrace = [];

    public function __construct(
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly Visibility $productVisibility,
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly StockResolverInterface $stockResolver
    ) {
    }

    public function resolveForPreview(array $sliderData): array
    {
        $resolved = $this->resolveProductIds($sliderData);
        $sourceType = (string)($sliderData['source_type'] ?? 'category');
        $sortingType = (string)($sliderData['sorting_type'] ?? 'newest');
        $desktopLimit = max(1, (int)($sliderData['max_products_desktop'] ?? 12));
        $tabletLimit = max(1, (int)($sliderData['max_products_tablet'] ?? 8));
        $mobileLimit = max(1, (int)($sliderData['max_products_mobile'] ?? 4));
        $fallbackLimit = (int)($sliderData['product_limit'] ?? $desktopLimit);
        $effectiveLimit = max(1, $fallbackLimit > 0 ? $fallbackLimit : $desktopLimit);

        $limitedIds = array_slice($resolved['ordered_ids'], 0, $effectiveLimit);

        $items = $this->hydrateProductRows($limitedIds, $resolved['context_category_ids']);
        $position = 1;
        foreach ($items as &$item) {
            $item['position'] = $position++;
        }
        unset($item);

        return [
            'source_type' => $sourceType,
            'sorting_type' => $sortingType,
            'total_found' => $resolved['total_found'],
            'desktop_limit' => $desktopLimit,
            'tablet_limit' => $tabletLimit,
            'mobile_limit' => $mobileLimit,
            'effective_limit' => $effectiveLimit,
            'items' => $items,
            'product_ids' => $limitedIds,
        ];
    }

    public function resolveProductIds(array $sliderData): array
    {
        $sourceType = (string)($sliderData['source_type'] ?? 'category');
        $sortingType = (string)($sliderData['sorting_type'] ?? 'newest');
        $sourceProductIds = $this->parseIds($sliderData['source_product_ids'] ?? '');
        $categoryIds = $this->parseIds($sliderData['category_ids'] ?? '');
        $mixedCategoryIds = $this->parseIds($sliderData['mixed_category_ids'] ?? '');
        $mixedProductIds = $this->parseIds($sliderData['mixed_product_ids'] ?? '');
        $excludedIds = $this->parseIds($sliderData['exclude_product_ids'] ?? '');
        $excludedIds = array_values(array_unique(array_merge($excludedIds, $this->parseIds($sliderData['mixed_excluded_product_ids'] ?? ''))));

        $includeChild = (int)($sliderData['include_child_categories'] ?? 1) === 1;

        $categoryPool = [];
        $manualPool = [];
        $contextCategoryIds = $categoryIds;

        if ($sourceType === 'category') {
            $categoryPool = $this->getCategoryProductIds($categoryIds, $includeChild);
        } elseif ($sourceType === 'products') {
            $manualPool = $sourceProductIds;
            $contextCategoryIds = [];
        } elseif ($sourceType === 'mixed') {
            $categoryPool = $this->getCategoryProductIds($mixedCategoryIds, $includeChild);
            $manualPool = $mixedProductIds;
            $contextCategoryIds = $mixedCategoryIds;
            $priority = (string)($sliderData['mixed_priority'] ?? 'category_then_manual');
            if ($priority === 'manual_then_category') {
                $candidateIds = $this->mergeByPriority($manualPool, $categoryPool);
            } else {
                $candidateIds = $this->mergeByPriority($categoryPool, $manualPool);
            }
            if ($excludedIds !== []) {
                $candidateIds = array_values(array_diff($candidateIds, $excludedIds));
            }

            $candidateIds = $this->filterDisplayableIds($candidateIds);

            $orderedIds = $this->sortIds($candidateIds, $sortingType, $sliderData, $contextCategoryIds, $sourceProductIds, $mixedProductIds);
            return [
                'ordered_ids' => $orderedIds,
                'total_found' => count($orderedIds),
                'context_category_ids' => $contextCategoryIds,
            ];
        }

        $candidateIds = array_values(array_unique(array_merge($categoryPool, $manualPool)));
        if ($excludedIds !== []) {
            $candidateIds = array_values(array_diff($candidateIds, $excludedIds));
        }

        $candidateIds = $this->filterDisplayableIds($candidateIds);

        $orderedIds = $this->sortIds($candidateIds, $sortingType, $sliderData, $contextCategoryIds, $sourceProductIds, $mixedProductIds);
        return [
            'ordered_ids' => $orderedIds,
            'total_found' => count($orderedIds),
            'context_category_ids' => $contextCategoryIds,
        ];
    }

    public function resolvePageIds(array $sliderData, int $page, int $pageSize): array
    {
        $resolved = $this->resolveProductIds($sliderData);
        $limit = $this->resolveConfiguredLimit($sliderData);
        $limitedOrderedIds = array_slice($resolved['ordered_ids'], 0, $limit);
        $limitedTotal = count($limitedOrderedIds);
        $page = max(1, $page);
        $pageSize = max(1, $pageSize);
        $offset = ($page - 1) * $pageSize;
        $ids = array_slice($limitedOrderedIds, $offset, $pageSize);

        return [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $limitedTotal,
            'ids' => $ids,
            'context_category_ids' => $resolved['context_category_ids'],
            'has_more' => ($offset + count($ids)) < $limitedTotal,
        ];
    }

    public function searchProducts(string $query, int $page, int $pageSize, array $selectedIds = []): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'sku', 'price', 'small_image']);
        $collection->addAttributeToFilter('status', 1);

        if ($query !== '') {
            $collection->addAttributeToFilter([
                ['attribute' => 'name', 'like' => '%' . $query . '%'],
                ['attribute' => 'sku', 'like' => '%' . $query . '%'],
            ]);
        }

        $collection->setPageSize(max(1, $pageSize));
        $collection->setCurPage(max(1, $page));
        $collection->setOrder('entity_id', 'DESC');

        $mediaBase = rtrim($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/');
        $items = [];
        foreach ($collection as $product) {
            $image = (string)$product->getData('small_image');
            $imageUrl = ($image && $image !== 'no_selection') ? ($mediaBase . '/catalog/product' . $image) : '';
            $stockItem = $this->stockRegistry->getStockItem((int)$product->getId());
            $items[] = [
                'id' => (int)$product->getId(),
                'sku' => (string)$product->getSku(),
                'name' => (string)$product->getName(),
                'price' => (float)$product->getPrice(),
                'stock' => ($stockItem && $stockItem->getIsInStock()) ? 'In Stock' : 'Out of Stock',
                'image' => $imageUrl,
                'selected' => in_array((int)$product->getId(), $selectedIds, true),
            ];
        }

        return [
            'items' => $items,
            'total' => (int)$collection->getSize(),
            'page' => max(1, $page),
            'page_size' => max(1, $pageSize),
        ];
    }

    private function sortIds(array $candidateIds, string $sortingType, array $sliderData, array $contextCategoryIds, array $sourceProductIds, array $mixedProductIds): array
    {
        if ($candidateIds === []) {
            return [];
        }

        if ($sortingType === 'random') {
            shuffle($candidateIds);
            return $candidateIds;
        }

        if ($sortingType === 'manual') {
            $manualOrder = $this->parseIds($sliderData['manual_sort_product_ids'] ?? '');
            if ($manualOrder === []) {
                $manualOrder = $sourceProductIds !== [] ? $sourceProductIds : $mixedProductIds;
            }

            if ($manualOrder !== []) {
                $ordered = [];
                $set = array_flip($candidateIds);
                foreach ($manualOrder as $id) {
                    if (isset($set[$id])) {
                        $ordered[] = $id;
                        unset($set[$id]);
                    }
                }
                foreach (array_keys($set) as $id) {
                    $ordered[] = (int)$id;
                }
                return $ordered;
            }
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addIdFilter($candidateIds);
        $collection->addAttributeToSelect(['name', 'sku', 'price', 'created_at', (string)($sliderData['custom_sort_attribute'] ?? '')]);

        if ($sortingType === 'price_asc') {
            $collection->addAttributeToSort('price', 'ASC');
        } elseif ($sortingType === 'price_desc') {
            $collection->addAttributeToSort('price', 'DESC');
        } elseif ($sortingType === 'name_asc' || $sortingType === 'name') {
            $collection->addAttributeToSort('name', 'ASC');
        } elseif ($sortingType === 'name_desc') {
            $collection->addAttributeToSort('name', 'DESC');
        } elseif ($sortingType === 'sku') {
            $collection->addAttributeToSort('sku', 'ASC');
        } elseif ($sortingType === 'newest') {
            $collection->addAttributeToSort('entity_id', 'DESC');
        } elseif ($sortingType === 'custom_attribute') {
            $custom = trim((string)($sliderData['custom_sort_attribute'] ?? ''));
            if ($custom !== '') {
                $collection->addAttributeToSort($custom, 'ASC');
            } else {
                $collection->addAttributeToSort('entity_id', 'DESC');
            }
        } elseif ($sortingType === 'category_position' && $contextCategoryIds !== []) {
            $this->applyCategoryPositionOrder($collection, $contextCategoryIds);
        } else {
            $collection->addAttributeToSort('entity_id', 'DESC');
        }

        $ordered = [];
        foreach ($collection as $product) {
            $ordered[] = (int)$product->getId();
        }
        return $ordered;
    }

    private function applyCategoryPositionOrder(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection, array $categoryIds): void
    {
        $connection = $this->resource->getConnection();
        $catTable = $this->resource->getTableName('catalog_category_product');
        $collection->getSelect()->joinLeft(
            ['ccp_pos' => $catTable],
            'e.entity_id = ccp_pos.product_id AND ccp_pos.category_id IN (' . implode(',', array_map('intval', $categoryIds)) . ')',
            ['category_position' => 'MIN(ccp_pos.position)']
        );
        $collection->getSelect()->group('e.entity_id');
        $collection->getSelect()->order('category_position ASC');
        $collection->getSelect()->order('e.entity_id DESC');
    }

    private function hydrateProductRows(array $ids, array $preferredCategoryIds = []): array
    {
        if ($ids === []) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addIdFilter($ids);
        $collection->addAttributeToSelect(['name', 'sku', 'price', 'small_image']);

        $mediaBase = rtrim($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/');
        $orderedMap = array_flip($ids);
        $rows = [];

        foreach ($collection as $product) {
            $productId = (int)$product->getId();
            $stockItem = $this->stockRegistry->getStockItem($productId);
            $image = (string)$product->getData('small_image');
            $imageUrl = ($image && $image !== 'no_selection') ? ($mediaBase . '/catalog/product' . $image) : '';
            $rows[] = [
                'id' => $productId,
                'sku' => (string)$product->getSku(),
                'name' => (string)$product->getName(),
                'price' => (float)$product->getPrice(),
                'stock' => ($stockItem && $stockItem->getIsInStock()) ? 'In Stock' : 'Out of Stock',
                'category' => $this->resolveCategoryLabel($productId, $preferredCategoryIds),
                'image' => $imageUrl,
                '_sort' => $orderedMap[$productId] ?? 999999,
            ];
        }

        usort($rows, static fn(array $a, array $b): int => ($a['_sort'] <=> $b['_sort']));
        foreach ($rows as &$row) {
            unset($row['_sort']);
        }
        unset($row);

        return $rows;
    }

    private function resolveCategoryLabel(int $productId, array $preferredCategoryIds): string
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_category_product');
        $select = $connection->select()->from($table, ['category_id'])->where('product_id = ?', $productId);
        if ($preferredCategoryIds !== []) {
            $select->where('category_id IN (?)', $preferredCategoryIds);
        }
        $select->order('position ASC')->limit(1);
        $categoryId = (int)$connection->fetchOne($select);
        if ($categoryId <= 0) {
            return '-';
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
            return (string)$category->getName();
        } catch (\Throwable) {
            return (string)$categoryId;
        }
    }

    private function getCategoryProductIds(array $categoryIds, bool $includeChild): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $expanded = [];
        foreach ($categoryIds as $categoryId) {
            $expanded[] = $categoryId;
            if ($includeChild) {
                try {
                    $category = $this->categoryRepository->get($categoryId);
                    $children = array_filter(array_map('intval', explode(',', (string)$category->getAllChildren(true))));
                    foreach ($children as $child) {
                        $expanded[] = $child;
                    }
                } catch (\Throwable) {
                }
            }
        }
        $expanded = array_values(array_unique(array_filter($expanded, static fn(int $v): bool => $v > 0)));
        if ($expanded === []) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_category_product');
        $select = $connection->select()->from($table, ['product_id'])->where('category_id IN (?)', $expanded);
        $ids = $connection->fetchCol($select);

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function parseIds(mixed $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = explode(',', (string)$value);
        }

        $ids = [];
        foreach ($parts as $part) {
            $id = (int)trim((string)$part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function mergeByPriority(array $first, array $second): array
    {
        $ordered = [];
        foreach ([$first, $second] as $pool) {
            foreach ($pool as $id) {
                if (!in_array($id, $ordered, true)) {
                    $ordered[] = $id;
                }
            }
        }

        return $ordered;
    }

    private function resolveConfiguredLimit(array $sliderData): int
    {
        $desktopLimit = max(1, (int)($sliderData['max_products_desktop'] ?? 12));
        $explicitLimit = (int)($sliderData['product_limit'] ?? 0);
        if ($explicitLimit > 0) {
            return $explicitLimit;
        }

        return $desktopLimit;
    }

    private function filterDisplayableIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $trace = ['input_ids' => $ids];

        $orderedMap = array_flip($ids);
        $collection = $this->productCollectionFactory->create();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $collection->setStoreId($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addIdFilter($ids);
        $collection->addAttributeToSelect(['entity_id', 'sku']);
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->setVisibility($this->productVisibility->getVisibleInCatalogIds());

        // sku => product_id, for the bulk MSI salability check below.
        $skuMap = [];
        foreach ($collection as $product) {
            $productId = (int)$product->getId();
            if (isset($orderedMap[$productId])) {
                $skuMap[(string)$product->getSku()] = $productId;
            }
        }

        $trace['after_status_and_visibility'] = $skuMap;

        if ($skuMap === []) {
            $trace['salable_ids'] = [];
            $trace['final_ids'] = [];
            $this->debugTrace[] = $trace;
            return [];
        }

        $salableIds = $this->filterSalableIds($skuMap, $trace);

        $result = array_values(array_intersect($ids, $salableIds));

        $trace['final_ids'] = $result;
        $this->debugTrace[] = $trace;

        return $result;
    }

    /**
     * @return array<int, array>
     */
    public function getDebugTrace(): array
    {
        return $this->debugTrace;
    }

    /**
     * Filters $skuMap (sku => product_id) down to only salable products,
     * using MSI's own bulk salability check rather than the legacy
     * `cataloginventory_stock_item.is_in_stock` flag. This matters for
     * configurable/bundle/grouped products in particular: MSI determines a
     * composite product's salability by aggregating its children's real
     * salable quantity across sources, which is not reliably reflected by
     * the legacy per-product stock item row (that mirror can lag behind
     * the true MSI state, especially for the parent's own row).
     *
     * @param array<string, int> $skuMap
     * @param array $trace by-reference debug trace entry, appended to
     * @return int[]
     */
    private function filterSalableIds(array $skuMap, array &$trace = []): array
    {
        try {
            $stockId = $this->resolveStockId();
            $trace['stock_id'] = $stockId;
            $results = $this->areProductsSalable->execute(array_keys($skuMap), $stockId);
        } catch (\Throwable $e) {
            // MSI stock/salability check unavailable — fail open rather
            // than hiding every product in the slider.
            $trace['salable_check_error'] = $e->getMessage();
            return array_values($skuMap);
        }

        $salableIds = [];
        $rawResults = [];
        foreach ($results as $result) {
            $rawResults[$result->getSku()] = [
                'stock_id' => $result->getStockId(),
                'is_salable' => $result->isSalable(),
            ];
            if ($result->isSalable() && isset($skuMap[$result->getSku()])) {
                $salableIds[] = $skuMap[$result->getSku()];
            }
        }
        $trace['salable_results'] = $rawResults;
        $trace['salable_ids'] = $salableIds;

        return $salableIds;
    }

    private function resolveStockId(): int
    {
        $websiteCode = (string)$this->storeManager->getWebsite()->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);

        return (int)$stock->getStockId();
    }
}
