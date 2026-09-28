<?php
declare(strict_types=1);

namespace ArtM\LuxuryCategory\Model\ResourceModel;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Lean, direct-SQL lookups for luxury-product membership, used by both the
 * top-menu "does this category have a Luxe item" check and the category
 * product collection filter.
 *
 * Deliberately bypasses `addAttributeToFilter()`/search-engine-backed
 * collection filtering: on stores using Elasticsearch/OpenSearch for
 * catalog listing, filtering by a custom attribute that way depends on
 * that attribute being indexed for search/layered-navigation and can
 * silently return unfiltered results if it isn't. Resolving product ids
 * directly via SQL and then restricting the collection with
 * `addIdFilter()` works identically regardless of catalog search engine,
 * since id filtering is never routed through the search adapter.
 */
class LuxuryProductChecker
{
    private const ATTRIBUTE_CODE = 'luxury';
    private const ENTITY_TYPE = 'catalog_product';

    private ?int $attributeId = null;

    /** @var array<int, bool>|null */
    private ?array $resultCache = null;

    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Returns the subset of $categoryIds that contain at least one
     * category-assigned product with luxury=1 (store-scoped). This is a
     * cheap existence check for menu rendering only — it intentionally
     * does not filter by product status/visibility; the category's own
     * product collection remains the source of truth for what actually
     * renders on the /luxe page.
     *
     * @param int[] $categoryIds
     * @return int[]
     */
    public function getCategoryIdsWithLuxuryProducts(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds), static fn(int $id): bool => $id > 0)));
        if ($categoryIds === []) {
            return [];
        }

        $attributeId = $this->getLuxuryAttributeId();
        if ($attributeId === null) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $categoryProductTable = $this->resourceConnection->getTableName('catalog_category_product');
        $attributeTable = $this->resourceConnection->getTableName('catalog_product_entity_int');
        $storeId = (int)$this->storeManager->getStore()->getId();

        // Deliberately no status/visibility join here: this query only
        // decides whether the menu should show a "Luxe" item at all, not
        // what products actually render — the category's own product
        // collection (via Magento\Catalog\Model\Layer\Category, already
        // status/visibility/store filtered) is the source of truth for
        // that. Keeping this query to a single cheap join keeps menu
        // rendering fast regardless of catalog size.
        $select = $connection->select()
            ->from(['ccp' => $categoryProductTable], ['category_id'])
            ->joinInner(
                ['av' => $attributeTable],
                'av.entity_id = ccp.product_id AND av.attribute_id = ' . (int)$attributeId
                . ' AND av.store_id IN (0, ' . $storeId . ')',
                []
            )
            ->where('ccp.category_id IN (?)', $categoryIds)
            ->group('ccp.category_id')
            ->having($this->buildStorePreferenceHavingClause($storeId));

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Returns the entity ids of every product assigned to $categoryId with
     * luxury=1 (store-scoped). Used to restrict the category's product
     * collection via `addIdFilter()` — see class docblock for why this is
     * done via id filtering rather than `addAttributeToFilter()`.
     *
     * @return int[]
     */
    public function getLuxuryProductIdsForCategory(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $attributeId = $this->getLuxuryAttributeId();
        if ($attributeId === null) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $categoryProductTable = $this->resourceConnection->getTableName('catalog_category_product');
        $attributeTable = $this->resourceConnection->getTableName('catalog_product_entity_int');
        $storeId = (int)$this->storeManager->getStore()->getId();

        $select = $connection->select()
            ->from(['ccp' => $categoryProductTable], ['product_id'])
            ->joinInner(
                ['av' => $attributeTable],
                'av.entity_id = ccp.product_id AND av.attribute_id = ' . (int)$attributeId
                . ' AND av.store_id IN (0, ' . $storeId . ')',
                []
            )
            ->where('ccp.category_id = ?', $categoryId)
            ->group('ccp.product_id')
            ->having($this->buildStorePreferenceHavingClause($storeId));

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Store-scoped EAV fallback: prefer the store-specific attribute value
     * row (store_id = current store) over the default row (store_id = 0),
     * expressed as a HAVING clause over the store-grouped aggregate so a
     * per-store override of `luxury` is respected without a second query.
     */
    private function buildStorePreferenceHavingClause(int $storeId): string
    {
        return 'MAX(CASE WHEN av.store_id = ' . $storeId . ' THEN av.value END) = 1'
            . ' OR (MAX(CASE WHEN av.store_id = ' . $storeId . ' THEN av.value END) IS NULL'
            . ' AND MAX(CASE WHEN av.store_id = 0 THEN av.value END) = 1)';
    }

    /**
     * Convenience single-category check, backed by the same bulk method
     * (results are cached per request so repeated calls are free).
     */
    public function categoryHasLuxuryProducts(int $categoryId): bool
    {
        if ($this->resultCache !== null && array_key_exists($categoryId, $this->resultCache)) {
            return $this->resultCache[$categoryId];
        }

        $found = $this->getCategoryIdsWithLuxuryProducts([$categoryId]);
        $result = in_array($categoryId, $found, true);

        $this->resultCache ??= [];
        $this->resultCache[$categoryId] = $result;

        return $result;
    }

    /**
     * Resolves and caches the attribute_id for the `luxury` attribute.
     * Returns null if the attribute does not exist (fail safe / no-op).
     */
    private function getLuxuryAttributeId(): ?int
    {
        if ($this->attributeId !== null) {
            return $this->attributeId;
        }

        try {
            $attribute = $this->eavConfig->getAttribute(self::ENTITY_TYPE, self::ATTRIBUTE_CODE);
            $attributeId = (int)$attribute->getAttributeId();
            if ($attributeId <= 0) {
                return null;
            }

            $this->attributeId = $attributeId;

            return $this->attributeId;
        } catch (\Throwable) {
            return null;
        }
    }
}
