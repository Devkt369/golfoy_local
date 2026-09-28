<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Model;

use ArtM\InventoryPlanner\Model\ResourceModel\InventorySnapshot as InventorySnapshotResource;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Snapshots MSI inventory (total salable qty, plus qty in each Settings-selected
 * source) for a specific set of products - called right after a sync aggregates
 * their sales, so the snapshot only ever covers products that were actually part
 * of that sync's order items, not the whole catalog.
 */
class InventorySnapshotUpdater
{
    private const CONFIG_PATH_SOURCES = 'artm_inventoryplanner/general/sources';

    private const IST_TIMEZONE = 'Asia/Kolkata';

    private InventorySnapshotResource $inventorySnapshotResource;

    private ResourceConnection $resourceConnection;

    private ScopeConfigInterface $scopeConfig;

    private SourceRepositoryInterface $sourceRepository;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private GetProductSalableQtyInterface $getProductSalableQty;

    private StockResolverInterface $stockResolver;

    private StoreManagerInterface $storeManager;

    public function __construct(
        InventorySnapshotResource $inventorySnapshotResource,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetProductSalableQtyInterface $getProductSalableQty,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->inventorySnapshotResource = $inventorySnapshotResource;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array<int, array{product_id:int, sku:string}> $products one entry per
     *     distinct product that appeared in the sync's aggregated order items
     */
    public function updateForProducts(array $products): void
    {
        if (!$products) {
            return;
        }

        $rows = [];
        foreach ($this->computeInventory($products) as $snapshot) {
            $rows[] = [
                'product_id' => $snapshot['product_id'],
                'sku' => $snapshot['sku'],
                'total_salable_qty' => $snapshot['total_salable_qty'],
                'total_selected_source_qty' => $snapshot['total_selected_source_qty'],
                'source_breakdown' => json_encode($snapshot['sources']),
                'calculated_at' => $snapshot['calculated_at'],
            ];
        }

        $this->inventorySnapshotResource->upsertSnapshots($rows);
    }

    /**
     * Computes MSI inventory (salable qty + qty in each Settings-selected source) for
     * the given products, as of right now - shared by the persisted sync-time
     * snapshot above and the Dashboard's on-demand "Live Inventory" column, which
     * calls this directly without persisting anything.
     *
     * @param array<int, array{product_id:int, sku:string}> $products
     * @return array<int, array{product_id:int, sku:string, total_salable_qty:float, total_selected_source_qty:float, sources:array<int, array{source_code:string, name:string, qty:float}>, calculated_at:string}>
     */
    public function computeInventory(array $products): array
    {
        if (!$products) {
            return [];
        }

        $selectedSources = $this->getSelectedSources();
        $sourceNames = $this->getSourceNames($selectedSources);
        $skus = array_values(array_unique(array_column($products, 'sku')));
        $sourceQtyBySku = $this->getSourceQtyBySku($skus, $selectedSources);
        $stockId = $this->resolveStockId();
        $now = (new \DateTimeImmutable('now', new \DateTimeZone(self::IST_TIMEZONE)))->format('Y-m-d H:i:s');

        $result = [];
        foreach ($products as $product) {
            $sku = $product['sku'];
            $sourceQtys = $sourceQtyBySku[$sku] ?? [];

            $breakdown = [];
            $totalSourceQty = 0.0;
            foreach ($selectedSources as $sourceCode) {
                $qty = (float) ($sourceQtys[$sourceCode] ?? 0.0);
                $breakdown[] = [
                    'source_code' => $sourceCode,
                    'name' => $sourceNames[$sourceCode] ?? $sourceCode,
                    'qty' => $qty,
                ];
                $totalSourceQty += $qty;
            }

            $result[] = [
                'product_id' => $product['product_id'],
                'sku' => $sku,
                'total_salable_qty' => $this->getSalableQty($sku, $stockId),
                'total_selected_source_qty' => $totalSourceQty,
                'sources' => $breakdown,
                'calculated_at' => $now,
            ];
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function getSelectedSources(): array
    {
        $saved = (string) $this->scopeConfig->getValue(self::CONFIG_PATH_SOURCES);
        if ($saved === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $saved))));
    }

    /**
     * @param string[] $sourceCodes
     * @return array<string, string> source_code => name
     */
    private function getSourceNames(array $sourceCodes): array
    {
        if (!$sourceCodes) {
            return [];
        }

        $names = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        foreach ($this->sourceRepository->getList($searchCriteria)->getItems() as $source) {
            if (in_array($source->getSourceCode(), $sourceCodes, true)) {
                $names[$source->getSourceCode()] = $source->getName();
            }
        }

        return $names;
    }

    /**
     * @param string[] $skus
     * @param string[] $sourceCodes
     * @return array<string, array<string, float>> sku => [source_code => qty]
     */
    private function getSourceQtyBySku(array $skus, array $sourceCodes): array
    {
        if (!$skus || !$sourceCodes) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('inventory_source_item');

        $select = $connection->select()
            ->from($table, ['sku', 'source_code', 'quantity'])
            ->where('sku IN (?)', $skus)
            ->where('source_code IN (?)', $sourceCodes);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[(string) $row['sku']][(string) $row['source_code']] = (float) $row['quantity'];
        }

        return $result;
    }

    private function resolveStockId(): int
    {
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $stock = $this->stockResolver->resolve('website', $websiteCode);

            return (int) $stock->getStockId();
        } catch (\Throwable $e) {
            return 1;
        }
    }

    private function getSalableQty(string $sku, int $stockId): float
    {
        try {
            return (float) $this->getProductSalableQty->execute($sku, $stockId);
        } catch (\Throwable $e) {
            // Product not managed by MSI / no stock item for this SKU yet - not an
            // error worth failing the whole sync over.
            return 0.0;
        }
    }
}
