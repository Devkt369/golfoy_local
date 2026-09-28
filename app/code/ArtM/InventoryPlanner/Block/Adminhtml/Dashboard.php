<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Block\Adminhtml;

use ArtM\InventoryPlanner\Model\ColumnGroupsProvider;
use ArtM\InventoryPlanner\Model\DashboardColumnList;
use ArtM\InventoryPlanner\Model\InventorySnapshotUpdater;
use ArtM\InventoryPlanner\Model\ResourceModel\InventorySnapshot as InventorySnapshotResource;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Dashboard report: reads ONLY from the already-synced artm_inventoryplanner_weekly_sales
 * table (never sales_order / sales_order_item directly - that only happens during
 * Sync Data / Sync Now), summed over two trailing "last N weeks" windows, to compare
 * each product's recent sales rate against its longer-run rate. Supports filtering,
 * sorting and paging entirely server-side via plain GET links/forms - no JS framework.
 */
class Dashboard extends Template
{
    /**
     * Selectable trailing-window sizes, in weeks, for both dropdowns.
     */
    private const WEEK_OPTIONS = [4, 8, 12, 16, 20, 24, 26, 52];

    private const DEFAULT_FROM_WEEKS = 16;
    private const DEFAULT_TO_WEEKS = 4;

    private const PAGE_SIZE_OPTIONS = [50, 100, 200, 500];
    private const DEFAULT_PAGE_SIZE = 50;

    /**
     * Row keys that clicking a column header is allowed to sort by.
     */
    private const SORTABLE_FIELDS = [
        'product_id', 'attribute_set', 'brand', 'from_qty', 'difference_qty', 'to_qty', 'growth_percent',
    ];

    private const DEFAULT_SORT_FIELD = 'product_id';
    private const DEFAULT_SORT_DIRECTION = 'asc';

    /**
     * Range-filter query param prefix => the row field it filters on.
     */
    private const RANGE_FILTERS = [
        'from_qty' => 'from_qty',
        'difference_qty' => 'difference_qty',
        'to_qty' => 'to_qty',
        'growth' => 'growth_percent',
    ];

    /**
     * Filter/sort/paging params that get carried forward on every sort/page/filter link.
     */
    private const QUERY_PARAM_NAMES = [
        'from_weeks', 'to_weeks', 'limit', 'page', 'sort', 'dir',
        'attribute_set', 'brand', 'sku', 'product_name', 'live_inventory',
        'from_qty_min', 'from_qty_max',
        'difference_qty_min', 'difference_qty_max',
        'to_qty_min', 'to_qty_max',
        'growth_min', 'growth_max',
    ];

    private const CONFIG_PATH_VISIBLE_COLUMNS = 'artm_inventoryplanner/general/visible_columns';

    private ResourceConnection $resourceConnection;

    private ProductCollectionFactory $productCollectionFactory;

    private EavConfig $eavConfig;

    private AttributeSetCollectionFactory $attributeSetCollectionFactory;

    private InventorySnapshotResource $inventorySnapshotResource;

    private InventorySnapshotUpdater $inventorySnapshotUpdater;

    private ColumnGroupsProvider $columnGroupsProvider;

    /**
     * @var array<int, array<string, mixed>>|null all matching rows, filtered + sorted, before paging
     */
    private ?array $allRows = null;

    /**
     * @var array<int, array<string, mixed>>|null the current page's rows, with live
     *     inventory attached if enabled - a separate cache from $allRows since it's
     *     only ever computed for the page actually being displayed
     */
    private ?array $pageRows = null;

    /**
     * @var array<int, string>|null attribute_set_id => name
     */
    private ?array $attributeSetNames = null;

    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection,
        ProductCollectionFactory $productCollectionFactory,
        EavConfig $eavConfig,
        AttributeSetCollectionFactory $attributeSetCollectionFactory,
        InventorySnapshotResource $inventorySnapshotResource,
        InventorySnapshotUpdater $inventorySnapshotUpdater,
        ColumnGroupsProvider $columnGroupsProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resourceConnection = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
        $this->inventorySnapshotResource = $inventorySnapshotResource;
        $this->inventorySnapshotUpdater = $inventorySnapshotUpdater;
        $this->columnGroupsProvider = $columnGroupsProvider;
    }

    public function isLiveInventoryEnabled(): bool
    {
        return (bool) $this->getRequest()->getParam('live_inventory');
    }

    public function getSaveColumnsFormActionUrl(): string
    {
        return $this->getUrl('artm_inventoryplanner/dashboard/saveColumns');
    }

    /**
     * Every Dashboard column, grouped for the "Columns" popup - one entry per
     * Settings-defined group (only the columns from it that are real, known
     * Dashboard columns), plus a trailing "Other Columns" group for anything not
     * assigned anywhere. Order mirrors DashboardColumnList::ALL_COLUMNS within
     * each group.
     *
     * @return array<int, array{key:string, name:string, columns: array<string, string>}>
     */
    public function getColumnGroupsForPopup(): array
    {
        $allColumns = DashboardColumnList::ALL_COLUMNS;
        $groupsData = $this->columnGroupsProvider->getGroupsData();

        $assigned = [];
        $result = [];

        foreach ($groupsData['groups'] as $index => $group) {
            $name = trim((string) ($group['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $columns = [];
            foreach ((array) ($group['columns'] ?? []) as $key) {
                $key = (string) $key;
                if (isset($allColumns[$key])) {
                    $columns[$key] = $allColumns[$key];
                    $assigned[$key] = true;
                }
            }

            if (!$columns) {
                continue;
            }

            $result[] = ['key' => 'group_' . $index, 'name' => $name, 'columns' => $columns];
        }

        $otherColumns = [];
        foreach ($allColumns as $key => $label) {
            if (!isset($assigned[$key])) {
                $otherColumns[$key] = $label;
            }
        }

        if ($otherColumns) {
            $result[] = ['key' => 'group_other', 'name' => (string) __('Other Columns'), 'columns' => $otherColumns];
        }

        return $result;
    }

    /**
     * Saved visible-column keys, or every column if never configured. Distinguishes
     * "never saved" (null - default to all, so a first-time admin doesn't land on a
     * blank table) from "explicitly saved as empty" (every box was unchecked, so the
     * table genuinely shows no columns).
     *
     * @return string[]
     */
    public function getVisibleColumns(): array
    {
        $raw = $this->_scopeConfig->getValue(self::CONFIG_PATH_VISIBLE_COLUMNS);
        if ($raw === null) {
            return array_keys(DashboardColumnList::ALL_COLUMNS);
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
    }

    public function isColumnVisible(string $columnKey): bool
    {
        return in_array($columnKey, $this->getVisibleColumns(), true);
    }

    /**
     * @return int[]
     */
    public function getWeekOptions(): array
    {
        return self::WEEK_OPTIONS;
    }

    public function getSelectedFromWeeks(): int
    {
        return $this->getSelectedWeeks('from_weeks', self::DEFAULT_FROM_WEEKS);
    }

    public function getSelectedToWeeks(): int
    {
        return $this->getSelectedWeeks('to_weeks', self::DEFAULT_TO_WEEKS);
    }

    public function getFromColumnLabel(): string
    {
        return sprintf('Unit Sold (%d Weeks)', $this->getSelectedFromWeeks());
    }

    public function getToColumnLabel(): string
    {
        return sprintf('Unit Sold (%d Weeks)', $this->getSelectedToWeeks());
    }

    public function getDifferenceColumnLabel(): string
    {
        return sprintf('Unit Sold (%d-%d Weeks)', $this->getSelectedFromWeeks(), $this->getSelectedToWeeks());
    }

    public function getFormActionUrl(): string
    {
        return $this->getUrl('artm_inventoryplanner/dashboard/index');
    }

    /**
     * @return int[]
     */
    public function getPageSizeOptions(): array
    {
        return self::PAGE_SIZE_OPTIONS;
    }

    public function getSelectedPageSize(): int
    {
        $value = (int) $this->getRequest()->getParam('limit', self::DEFAULT_PAGE_SIZE);

        return in_array($value, self::PAGE_SIZE_OPTIONS, true) ? $value : self::DEFAULT_PAGE_SIZE;
    }

    public function getCurrentPage(): int
    {
        $totalPages = $this->getTotalPages();
        $page = (int) $this->getRequest()->getParam('page', 1);

        return max(1, min($page, $totalPages));
    }

    public function getTotalRecordsCount(): int
    {
        return count($this->computeAllRows());
    }

    public function getTotalPages(): int
    {
        return max(1, (int) ceil($this->getTotalRecordsCount() / $this->getSelectedPageSize()));
    }

    /**
     * 1-based index of the first record shown on the current page (0 if there are none).
     */
    public function getRangeStart(): int
    {
        if ($this->getTotalRecordsCount() === 0) {
            return 0;
        }

        return ($this->getCurrentPage() - 1) * $this->getSelectedPageSize() + 1;
    }

    /**
     * 1-based index of the last record shown on the current page.
     */
    public function getRangeEnd(): int
    {
        return min($this->getCurrentPage() * $this->getSelectedPageSize(), $this->getTotalRecordsCount());
    }

    public function getPageUrl(int $page): string
    {
        $params = array_merge($this->getCurrentQueryParams(), ['page' => $page]);

        return $this->getUrl('artm_inventoryplanner/dashboard/index', ['_query' => $params]);
    }

    /**
     * URL for a sortable column header: sorts by $field, flipping direction if it's
     * already the active sort field, and resets back to page 1.
     */
    public function getSortUrl(string $field): string
    {
        $direction = ($this->getSelectedSortField() === $field && $this->getSelectedSortDirection() === 'asc')
            ? 'desc'
            : 'asc';

        $params = array_merge($this->getCurrentQueryParams(), ['sort' => $field, 'dir' => $direction, 'page' => 1]);

        return $this->getUrl('artm_inventoryplanner/dashboard/index', ['_query' => $params]);
    }

    /**
     * "▲"/"▼" next to the column currently being sorted by, blank otherwise.
     */
    public function getSortIndicator(string $field): string
    {
        if ($this->getSelectedSortField() !== $field) {
            return '';
        }

        return $this->getSelectedSortDirection() === 'asc' ? '&#9650;' : '&#9660;';
    }

    /**
     * @return array<int, array{value:int, label:string}>
     */
    public function getAttributeSetOptions(): array
    {
        $options = [];
        foreach ($this->getAttributeSetNames() as $id => $name) {
            $options[] = ['value' => $id, 'label' => $name];
        }

        return $options;
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    public function getBrandOptions(): array
    {
        $options = [];

        try {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'brand');
            foreach ($attribute->getSource()->getAllOptions(false) as $option) {
                if ($option['value'] === '' || $option['value'] === null) {
                    continue;
                }
                $options[] = ['value' => (string) $option['value'], 'label' => (string) $option['label']];
            }
        } catch (\Throwable $e) {
            // Attribute missing/misconfigured on this instance - no brand filter options.
        }

        return $options;
    }

    /**
     * @return string[]
     */
    public function getSelectedAttributeSetFilters(): array
    {
        return $this->getSelectedMultiFilter('attribute_set');
    }

    /**
     * @return string[]
     */
    public function getSelectedBrandFilters(): array
    {
        return $this->getSelectedMultiFilter('brand');
    }

    public function isAttributeSetSelected(string $id): bool
    {
        return in_array($id, $this->getSelectedAttributeSetFilters(), true);
    }

    public function isBrandSelected(string $value): bool
    {
        return in_array($value, $this->getSelectedBrandFilters(), true);
    }

    public function getSkuFilterValue(): string
    {
        return (string) $this->getRequest()->getParam('sku', '');
    }

    public function getProductNameFilterValue(): string
    {
        return (string) $this->getRequest()->getParam('product_name', '');
    }

    /**
     * SKU search accepts several SKUs at once, comma-separated (pasted or typed one
     * by one) - a row matches if its SKU contains ANY of these terms.
     *
     * @return string[]
     */
    private function getSkuFilterTerms(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->getSkuFilterValue()))));
    }

    public function getRangeFilterMin(string $prefix): string
    {
        return (string) $this->getRequest()->getParam($prefix . '_min', '');
    }

    public function getRangeFilterMax(string $prefix): string
    {
        return (string) $this->getRequest()->getParam($prefix . '_max', '');
    }

    /**
     * URL that clears every active filter but keeps the week/page-size/sort selections.
     */
    public function getClearFiltersUrl(): string
    {
        $filterParams = array_merge(
            ['attribute_set', 'brand', 'sku', 'product_name'],
            array_merge(...array_map(
                static fn (string $prefix): array => [$prefix . '_min', $prefix . '_max'],
                array_keys(self::RANGE_FILTERS)
            ))
        );

        return $this->getUrlWithoutParams($filterParams);
    }

    public function hasActiveFilters(): bool
    {
        return (bool) $this->getActiveFilterChips();
    }

    /**
     * One entry per currently-applied filter, for the "chips" row above the table.
     *
     * @return array<int, array{label: string, removeUrl: string}>
     */
    public function getActiveFilterChips(): array
    {
        $chips = [];

        foreach ($this->getSelectedAttributeSetFilters() as $id) {
            $name = $this->getAttributeSetNames()[(int) $id] ?? $id;
            $chips[] = [
                'label' => sprintf('Attribute Set: %s', $name),
                'removeUrl' => $this->getUrlWithoutFilterValue('attribute_set', $id),
            ];
        }

        foreach ($this->getSelectedBrandFilters() as $value) {
            $name = $value;
            foreach ($this->getBrandOptions() as $option) {
                if ($option['value'] === $value) {
                    $name = $option['label'];
                    break;
                }
            }
            $chips[] = [
                'label' => sprintf('Brand: %s', $name),
                'removeUrl' => $this->getUrlWithoutFilterValue('brand', $value),
            ];
        }

        foreach (self::RANGE_FILTERS as $prefix => $field) {
            $min = $this->getRangeFilterMin($prefix);
            $max = $this->getRangeFilterMax($prefix);
            if ($min === '' && $max === '') {
                continue;
            }

            $chips[] = [
                'label' => sprintf('%s: %s - %s', $this->getRangeFilterLabel($prefix), $min !== '' ? $min : '…', $max !== '' ? $max : '…'),
                'removeUrl' => $this->getUrlWithoutParams([$prefix . '_min', $prefix . '_max']),
            ];
        }

        if ($this->getSkuFilterValue() !== '') {
            $chips[] = [
                'label' => sprintf('SKU: %s', $this->getSkuFilterValue()),
                'removeUrl' => $this->getUrlWithoutParams(['sku']),
            ];
        }

        if ($this->getProductNameFilterValue() !== '') {
            $chips[] = [
                'label' => sprintf('Product: %s', $this->getProductNameFilterValue()),
                'removeUrl' => $this->getUrlWithoutParams(['product_name']),
            ];
        }

        return $chips;
    }

    /**
     * Main entry point used by the template - one PAGE of rows, ready to render.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReportRows(): array
    {
        if ($this->pageRows !== null) {
            return $this->pageRows;
        }

        $allRows = $this->computeAllRows();
        $pageSize = $this->getSelectedPageSize();
        $offset = ($this->getCurrentPage() - 1) * $pageSize;
        $pageRows = array_slice($allRows, $offset, $pageSize);

        if ($this->isLiveInventoryEnabled()) {
            $pageRows = $this->attachLiveInventory($pageRows);
        }

        $this->pageRows = $pageRows;

        return $this->pageRows;
    }

    /**
     * Computes live MSI inventory only for the products on the CURRENT page - not
     * every matching row - since each product needs its own salable-qty lookup and
     * doing that across a whole (possibly large) filtered result set would be far
     * too slow for a page load.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function attachLiveInventory(array $rows): array
    {
        $products = [];
        foreach ($rows as $row) {
            $products[] = ['product_id' => $row['product_id'], 'sku' => $row['sku']];
        }

        $liveByProductId = [];
        foreach ($this->inventorySnapshotUpdater->computeInventory($products) as $snapshot) {
            $liveByProductId[$snapshot['product_id']] = $this->formatInventoryDisplay(
                $snapshot['total_selected_source_qty'],
                $snapshot['sources'],
                $snapshot['calculated_at']
            );
        }

        foreach ($rows as &$row) {
            $row['live_inventory'] = $liveByProductId[$row['product_id']] ?? null;
        }

        return $rows;
    }

    private function getRangeFilterLabel(string $prefix): string
    {
        return match ($prefix) {
            'from_qty' => $this->getFromColumnLabel(),
            'difference_qty' => $this->getDifferenceColumnLabel(),
            'to_qty' => $this->getToColumnLabel(),
            'growth' => (string) __('Growth %'),
            default => $prefix,
        };
    }

    private function getSelectedSortField(): string
    {
        $field = (string) $this->getRequest()->getParam('sort', self::DEFAULT_SORT_FIELD);

        return in_array($field, self::SORTABLE_FIELDS, true) ? $field : self::DEFAULT_SORT_FIELD;
    }

    private function getSelectedSortDirection(): string
    {
        $direction = strtolower((string) $this->getRequest()->getParam('dir', self::DEFAULT_SORT_DIRECTION));

        return $direction === 'desc' ? 'desc' : 'asc';
    }

    /**
     * @return array<string, string> only the whitelisted filter/sort/paging params
     *     that are actually present on the current request
     */
    /**
     * Public because the "Columns" popup renders these as hidden fields in its own
     * (separate, POST) form, so saving column visibility can redirect back to the
     * exact same weeks/filters/sort/page state instead of resetting it.
     *
     * @return array<string, string> only the whitelisted filter/sort/paging params
     *     that are actually present on the current request
     */
    public function getCurrentQueryParams(): array
    {
        $params = [];
        foreach (self::QUERY_PARAM_NAMES as $name) {
            $value = $this->getRequest()->getParam($name);
            if ($value !== null && $value !== '') {
                $params[$name] = $value;
            }
        }

        return $params;
    }

    public function getUrlWithoutParams(array $namesToRemove): string
    {
        $params = $this->getCurrentQueryParams();
        foreach ($namesToRemove as $name) {
            unset($params[$name]);
        }
        $params['page'] = 1;

        return $this->getUrl('artm_inventoryplanner/dashboard/index', ['_query' => $params]);
    }

    /**
     * Like getUrlWithoutParams(), but for a multi-value filter (attribute_set[]/brand[])
     * it drops just the one selected value, keeping the rest of that filter intact.
     */
    public function getUrlWithoutFilterValue(string $paramName, string $value): string
    {
        $params = $this->getCurrentQueryParams();

        if (isset($params[$paramName])) {
            $remaining = array_values(array_diff((array) $params[$paramName], [$value]));
            if ($remaining) {
                $params[$paramName] = $remaining;
            } else {
                unset($params[$paramName]);
            }
        }
        $params['page'] = 1;

        return $this->getUrl('artm_inventoryplanner/dashboard/index', ['_query' => $params]);
    }

    /**
     * @return string[]
     */
    private function getSelectedMultiFilter(string $paramName): array
    {
        $value = (array) $this->getRequest()->getParam($paramName, []);

        return array_values(array_unique(array_map('strval', array_filter(
            $value,
            static fn ($item): bool => $item !== null && $item !== ''
        ))));
    }

    private function getSelectedWeeks(string $paramName, int $default): int
    {
        $value = (int) $this->getRequest()->getParam($paramName, $default);

        return in_array($value, self::WEEK_OPTIONS, true) ? $value : $default;
    }

    /**
     * Builds every matching row (across the whole result set, not just the current
     * page), then filters and sorts it. Cached for the life of the request since the
     * table, the pager and the filter chips all need the full, filtered, sorted set.
     *
     * @return array<int, array<string, mixed>>
     */
    private function computeAllRows(): array
    {
        if ($this->allRows !== null) {
            return $this->allRows;
        }

        $fromWeeks = $this->getSelectedFromWeeks();
        $toWeeks = $this->getSelectedToWeeks();
        $today = new \DateTimeImmutable('today');

        // Two independent trailing windows, both ending "today" - e.g. "From 16 weeks"
        // sums every synced week from (today - 16 weeks) through today; "To 4 weeks"
        // sums the last 4 weeks of that same synced data.
        $fromQtyByProduct = $this->sumStoredQty($today->modify(sprintf('-%d weeks', $fromWeeks)), $today);
        $toQtyByProduct = $this->sumStoredQty($today->modify(sprintf('-%d weeks', $toWeeks)), $today);

        $productIds = array_unique(array_merge(array_keys($fromQtyByProduct), array_keys($toQtyByProduct)));

        if (!$productIds) {
            $this->allRows = [];

            return $this->allRows;
        }

        $products = $this->loadProducts($productIds);
        $snapshots = $this->inventorySnapshotResource->getSnapshotsForProducts($productIds);

        $rows = [];
        foreach ($products as $product) {
            $productId = (int) $product->getId();
            $rows[] = $this->buildRow(
                $product,
                $fromQtyByProduct[$productId] ?? 0.0,
                $toQtyByProduct[$productId] ?? 0.0,
                $fromWeeks,
                $toWeeks,
                $snapshots[$productId] ?? null
            );
        }

        $rows = $this->applyFilters($rows);
        $this->sortRows($rows);

        $this->allRows = $rows;

        return $this->allRows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function applyFilters(array $rows): array
    {
        $attributeSetFilters = $this->getSelectedAttributeSetFilters();
        $brandFilters = $this->getSelectedBrandFilters();
        $skuFilterTerms = $this->getSkuFilterTerms();
        $productNameFilter = $this->getProductNameFilterValue();

        $ranges = [];
        foreach (self::RANGE_FILTERS as $prefix => $field) {
            $min = $this->getRangeFilterMin($prefix);
            $max = $this->getRangeFilterMax($prefix);
            $ranges[$field] = [
                $min !== '' ? (float) $min : null,
                $max !== '' ? (float) $max : null,
            ];
        }

        return array_values(array_filter(
            $rows,
            static function (array $row) use ($attributeSetFilters, $brandFilters, $skuFilterTerms, $productNameFilter, $ranges): bool {
                if ($attributeSetFilters && !in_array((string) $row['attribute_set_id'], $attributeSetFilters, true)) {
                    return false;
                }

                if ($brandFilters) {
                    $rawValues = $row['brand_value'] !== null
                        ? array_map('trim', explode(',', (string) $row['brand_value']))
                        : [];
                    if (!array_intersect($brandFilters, $rawValues)) {
                        return false;
                    }
                }

                if ($skuFilterTerms) {
                    $sku = (string) $row['sku'];
                    $matchesAnyTerm = false;
                    foreach ($skuFilterTerms as $term) {
                        if (stripos($sku, $term) !== false) {
                            $matchesAnyTerm = true;
                            break;
                        }
                    }
                    if (!$matchesAnyTerm) {
                        return false;
                    }
                }

                if ($productNameFilter !== '' && stripos((string) $row['product_name'], $productNameFilter) === false) {
                    return false;
                }

                foreach ($ranges as $field => [$min, $max]) {
                    $value = $row[$field];
                    if ($min !== null && ($value === null || $value < $min)) {
                        return false;
                    }
                    if ($max !== null && ($value === null || $value > $max)) {
                        return false;
                    }
                }

                return true;
            }
        ));
    }

    private function sortRows(array &$rows): void
    {
        $field = $this->getSelectedSortField();
        $direction = $this->getSelectedSortDirection();

        usort($rows, static function (array $a, array $b) use ($field, $direction): int {
            $left = $a[$field];
            $right = $b[$field];

            // Push nulls (e.g. Growth % with no comparison base) to the end regardless of direction.
            if ($left === null && $right === null) {
                return 0;
            }
            if ($left === null) {
                return 1;
            }
            if ($right === null) {
                return -1;
            }

            $result = (is_string($left) && is_string($right)) ? strcasecmp($left, $right) : ($left <=> $right);

            return $direction === 'desc' ? -$result : $result;
        });
    }

    /**
     * Sums qty_sold per product from the synced weekly_sales table for weeks whose
     * start falls within [$cutoff, $today]. This is the ONLY place the dashboard
     * reads sales data - it never touches sales_order / sales_order_item.
     *
     * @return array<int, float> product_id => qty sold
     */
    private function sumStoredQty(\DateTimeImmutable $cutoff, \DateTimeImmutable $today): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('artm_inventoryplanner_weekly_sales');

        $select = $connection->select()
            ->from($table, [
                'product_id',
                'qty' => new \Zend_Db_Expr('SUM(qty_sold)'),
            ])
            ->where('week_start >= ?', $cutoff->format('Y-m-d'))
            ->where('week_start <= ?', $today->format('Y-m-d'))
            ->group('product_id');

        return array_map('floatval', $connection->fetchPairs($select));
    }

    private function loadProducts(array $productIds): ProductCollection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'sku', 'brand']);
        $collection->addFieldToFilter('entity_id', ['in' => $productIds]);

        return $collection;
    }

    /**
     * @return array<int, string> attribute_set_id => name
     */
    private function getAttributeSetNames(): array
    {
        if ($this->attributeSetNames !== null) {
            return $this->attributeSetNames;
        }

        $names = [];
        // setEntityTypeFilter() filters on the numeric entity_type_id column, not the
        // "catalog_product" entity type code, so it must be resolved first.
        $entityTypeId = (int) $this->eavConfig->getEntityType(Product::ENTITY)->getId();

        $collection = $this->attributeSetCollectionFactory->create();
        $collection->setEntityTypeFilter($entityTypeId);

        foreach ($collection as $attributeSet) {
            $names[(int) $attributeSet->getId()] = $attributeSet->getAttributeSetName();
        }

        $this->attributeSetNames = $names;

        return $this->attributeSetNames;
    }

    /**
     * @param array<string, mixed>|null $snapshot raw row from artm_inventoryplanner_inventory_snapshot
     */
    private function buildRow(
        Product $product,
        float $fromQty,
        float $toQty,
        int $fromWeeks,
        int $toWeeks,
        ?array $snapshot
    ): array {
        // Growth compares the recent weekly rate against the EARLIER weekly rate -
        // "earlier" meaning the difference window (From weeks minus To weeks), not the
        // whole From window (which already includes the To weeks).
        // Example: From 16 weeks = 64 sold, To 4 weeks = 20 sold.
        // Earlier weeks = 16 - 4 = 12, earlier qty = 64 - 20 = 44 -> 44/12 = 3.67/week.
        // Recent rate = 20/4 = 5/week. Growth = ((5 - 3.67) / 3.67) * 100 = 36%.
        $differenceQty = $fromQty - $toQty;
        $differenceWeeks = $fromWeeks - $toWeeks;

        $earlierAvg = $differenceWeeks > 0 ? $differenceQty / $differenceWeeks : 0.0;
        $recentAvg = $toWeeks > 0 ? $toQty / $toWeeks : 0.0;

        $growthPercent = $earlierAvg > 0.0
            ? (int) round((($recentAvg - $earlierAvg) / $earlierAvg) * 100)
            : null;
        $trend = $this->calculateTrend($recentAvg, $earlierAvg);

        $attributeSetNames = $this->getAttributeSetNames();
        $attributeSetId = (int) $product->getAttributeSetId();

        return [
            'product_id' => (int) $product->getId(),
            'product_name' => $product->getName(),
            'sku' => $product->getSku(),
            'attribute_set_id' => $attributeSetId,
            'attribute_set' => $attributeSetNames[$attributeSetId] ?? null,
            'brand_value' => $product->getData('brand'),
            'brand' => $this->getAttributeLabel($product, 'brand'),
            'from_qty' => $fromQty,
            'difference_qty' => $differenceQty,
            'to_qty' => $toQty,
            'growth_percent' => $growthPercent,
            'trend' => $trend,
            'captured_inventory' => $this->buildCapturedInventoryDisplay($snapshot),
        ];
    }

    /**
     * @param array<string, mixed>|null $snapshot raw row from artm_inventoryplanner_inventory_snapshot
     * @return array<string, mixed>|null null if this product has never been part of a
     *     sync (so there's nothing captured yet)
     */
    private function buildCapturedInventoryDisplay(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        $sources = json_decode((string) $snapshot['source_breakdown'], true);

        return $this->formatInventoryDisplay(
            (float) $snapshot['total_selected_source_qty'],
            is_array($sources) ? $sources : [],
            (string) $snapshot['calculated_at']
        );
    }

    /**
     * Shared display shape for both the captured (sync-time) and live (on-demand)
     * inventory columns.
     *
     * @param array<int, array{source_code?:string, name?:string, qty?:float}> $sources
     * @return array{calculated_at:string, total_selected_source_qty:float, sources:array<int, array{name:string, qty:float}>}
     */
    private function formatInventoryDisplay(float $totalSourceQty, array $sources, string $calculatedAt): array
    {
        return [
            'calculated_at' => (new \DateTimeImmutable($calculatedAt))->format('d-M-Y g:i A') . ' IST',
            'total_selected_source_qty' => $totalSourceQty,
            'sources' => array_map(static fn (array $source): array => [
                'name' => (string) ($source['name'] ?? $source['source_code'] ?? ''),
                'qty' => (float) ($source['qty'] ?? 0),
            ], $sources),
        ];
    }

    private function getAttributeLabel(Product $product, string $attributeCode): ?string
    {
        try {
            $value = $product->getAttributeText($attributeCode);
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if ($value !== false && $value !== null && $value !== '') {
                return (string) $value;
            }
        } catch (\Throwable $e) {
            // Attribute isn't source-based on this instance - fall back to the raw value below.
        }

        $raw = $product->getData($attributeCode);

        return $raw !== null && $raw !== '' ? (string) $raw : null;
    }

    private function calculateTrend(float $toAvg, float $fromAvg): string
    {
        if ($toAvg > $fromAvg) {
            return 'up';
        }
        if ($toAvg < $fromAvg) {
            return 'down';
        }

        return 'flat';
    }
}
