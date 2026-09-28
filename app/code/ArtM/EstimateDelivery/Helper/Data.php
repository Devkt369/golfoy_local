<?php
namespace ArtM\EstimateDelivery\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory as SourceItemCollectionFactory;
use Magento\Framework\App\CacheInterface;
use ArtM\EstimateDelivery\Model\DeliveryCache;
use ArtM\EstimateDelivery\Model\DeliveryCacheFactory;
use ArtM\EstimateDelivery\Model\ResourceModel\DeliveryCache as DeliveryCacheResource;
use Magento\Catalog\Model\Product\Type;
use DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const CACHE_KEY = 'artm_estimatedelivery_source_config';
    private const PARTNER_STORE_ID = 5;

    protected $productRepository;
    protected $sourceItemCollection;
    protected $cache;
    protected $deliveryCacheFactory;
    protected $deliveryCacheResource;
    protected $storeManager;
    
    // Performance optimization caches
    // NOTE: these are keyed by store id (see keys like "{storeId}:{sku}") because
    // config (own/other/custom sources, dispatch/delivery days, enabled flag) is
    // store-scoped. Keying by SKU/value alone previously leaked one store's config
    // into another store's requests.
    protected $productSourceCache = [];
    protected $configCache = [];
    protected $enabledStatusCache = [];

    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        SourceItemCollectionFactory $sourceItemCollection,
        CacheInterface $cache,
        DeliveryCacheFactory $deliveryCacheFactory,
        DeliveryCacheResource $deliveryCacheResource,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->sourceItemCollection = $sourceItemCollection;
        $this->cache = $cache;
        $this->deliveryCacheFactory = $deliveryCacheFactory;
        $this->deliveryCacheResource = $deliveryCacheResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Return Estimated Delivery Date for given SKU
     */
    public function getEstimatedDeliveryDate(string $sku, ?int $storeId = null): array
    {
        try {
            $dispatch = '';
            $delivery = '';
            $totalDays = '';
            $productSource = '';
            $group = '';
            $expressShipping = '';

            if($this->getConfigValue('artm_estimatedelivery/general/enabled', $storeId) != 1) {
                return [
                    'dispatch_days'            => 0,
                    'delivery_days'            => 0,
                    'total_days'               => 0,
                    'estimated_delivery_date'  => null,
                    'source_code'              => null,
                    'group'                    => null,
                    'express_shipping'         => false,
                    'show_estimation'          => false
                ];
            }

            $config = $this->getCachedConfig($storeId);

            $productSource = $this->getProductSourceCached($sku, $storeId); // Use cached version

            $group = $this->getSourceGroupBySourceCode($productSource, $config);

            $showEstimation = true;

            $forceProductDispatchTransitDays = $this->isForceProductDispatchTransitDaysEnabled($sku);
            if ($forceProductDispatchTransitDays) {
                $attributeBasedEstimate = $this->getEstimatedDeliveryFromProductAttributes($sku);
                if ($attributeBasedEstimate !== null) {
                    $dispatch = (int)$attributeBasedEstimate['dispatch_days'];
                    $delivery = (int)$attributeBasedEstimate['delivery_days'];
                    $totalDays = (int)$attributeBasedEstimate['total_days'];
                    $date = new \DateTime((string)$attributeBasedEstimate['estimated_delivery_date']);
                }
            } elseif (!$productSource) {

                if ($this->isStorePartner($storeId)) {
                    // Partner store only displays the estimation when the product's
                    // stock source matches one of the explicitly configured groups
                    // (own/other/custom). No match here means none of those groups
                    // are configured for this product's source, so do not fall back
                    // to product attributes - hide the estimation entirely.
                    return [
                        'dispatch_days'            => 0,
                        'delivery_days'            => 0,
                        'total_days'               => 0,
                        'estimated_delivery_date'  => null,
                        'source_code'              => null,
                        'group'                    => null,
                        'express_shipping'         => false,
                        'show_estimation'          => false
                    ];
                }

                $attributeBasedEstimate = $this->getEstimatedDeliveryFromProductAttributes($sku);
                if ($attributeBasedEstimate !== null) {
                    $dispatch = (int)$attributeBasedEstimate['dispatch_days'];
                    $delivery = (int)$attributeBasedEstimate['delivery_days'];
                    $totalDays = (int)$attributeBasedEstimate['total_days'];
                    $date = new \DateTime((string)$attributeBasedEstimate['estimated_delivery_date']);
                }
            }else{
                
                $groupDispatchRaw = $config[$group]['dispatch'] ?? null;
                $groupDeliveryRaw = $config[$group]['delivery'] ?? null;
                $groupDispatchEmpty = ($groupDispatchRaw === null || $groupDispatchRaw === '');
                $groupDeliveryEmpty = ($groupDeliveryRaw === null || $groupDeliveryRaw === '');

                if ($this->isStorePartner($storeId) && $groupDispatchEmpty && $groupDeliveryEmpty) {
                    // Partner store: the source matched a configured group, but
                    // that group's dispatch AND delivery days were both left
                    // blank by the admin. Don't default to 0 days here - hide
                    // the estimation rather than showing a misleading "today".
                    // (If only one of the two is filled in, still show using
                    // the other value plus 0 for the blank one, as before.)
                    return [
                        'dispatch_days'            => 0,
                        'delivery_days'            => 0,
                        'total_days'               => 0,
                        'estimated_delivery_date'  => null,
                        'source_code'              => $productSource,
                        'group'                    => $group,
                        'express_shipping'         => false,
                        'show_estimation'          => false
                    ];
                }

                $dispatch = $groupDispatchEmpty ? 0 : (int)$groupDispatchRaw;
                $delivery = $groupDeliveryEmpty ? 0 : (int)$groupDeliveryRaw;
                $totalDays = $dispatch + $delivery;
                $date = new \DateTime();
                $date->modify("+{$totalDays} days");
            }

            $expressShipping = ($group === 'own');
            
            return [
                'dispatch_days'            => $dispatch ?? 0,
                'delivery_days'            => $delivery ?? 0,
                'total_days'               => $totalDays ?? 0,
                'estimated_delivery_date'  => $date ? $date->format('Y-m-d') : null,
                'source_code'              => $productSource,
                'group'                    => $group,
                'express_shipping'         => $expressShipping,
                'show_estimation'          => $showEstimation
            ];
        } catch (\Exception $e) {
            return [
                'dispatch_days'            => 0,
                'delivery_days'            => 0,
                'total_days'               => 0,
                'estimated_delivery_date'  => null,
                'source_code'              => null,
                'group'                    => null,
                'express_shipping'         => false,
                'show_estimation'          => false
            ];
        }
    }

    /**
     * Safely read a product attribute value even when the attribute is missing or not loaded.
     */
    public function getProductAttributeText($product, string $attributeCode): ?string
    {
        if (!$product || !$attributeCode) {
            return null;
        }

        try {
            $resource = $product->getResource();
            if ($resource && $resource->getAttribute($attributeCode)) {
                $value = $product->getAttributeText($attributeCode);
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                if ($value !== null && $value !== '') {
                    return (string)$value;
                }
            }
        } catch (\Throwable $e) {
            // Fall back to raw data below.
        }

        try {
            $rawValue = $product->getData($attributeCode);
            if (is_array($rawValue)) {
                return implode(',', array_filter($rawValue));
            }

            return $rawValue === null || $rawValue === '' ? null : (string)$rawValue;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Calculate delivery estimate from product attributes using SKU.
     *
     * Returns null when attributes/product cannot be resolved.
     *
     * @return array<string, int|string>|null
     */
    public function getEstimatedDeliveryFromProductAttributes(string $sku): ?array
    {
        try {
            $product = $this->productRepository->get($sku);

            $dispatchValue = $this->getProductAttributeText($product, 'dispatch');
            $deliveryValue = $this->getProductAttributeText($product, 'shippingdays');

            $dispatchText = (string)$dispatchValue;
            $deliveryText = (string)$deliveryValue;

            $dispatch = (int)preg_replace('/[^0-9]/', '', $dispatchText);
            $delivery = (int)preg_replace('/[^0-9]/', '', $deliveryText);
            $totalDays = $dispatch + $delivery;

            $date = new \DateTime();
            $date->modify("+{$totalDays} days");

            return [
                'dispatch_days' => $dispatch,
                'delivery_days' => $delivery,
                'total_days' => $totalDays,
                'estimated_delivery_date' => $date->format('Y-m-d'),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Returns true when product attribute forceproductdispatchtransitdays is explicitly enabled.
     */
    public function isForceProductDispatchTransitDaysEnabled(string $sku): bool
    {
        try {
            $product = $this->productRepository->get($sku);

            $rawValue = $product->getData('forceproductdispatchtransitdays');
            if (in_array((string)$rawValue, ['1', 'yes', 'Yes', 'true', 'True'], true)) {
                return true;
            }

            $textValue = $this->getProductAttributeText($product, 'forceproductdispatchtransitdays');
            return in_array((string)$textValue, ['1', 'yes', 'Yes', 'true', 'True'], true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Determine product source using MSI with optimized caching
     */
    public function getProductSource($sku)
    {
        // Use cached version for better performance
        return $this->getProductSourceCached($sku);
    }
    
    /**
     * Internal method for actual source calculation
     */
    private function calculateProductSource($sku, ?int $storeId = null)
    {
        $config = $this->getCachedConfig($storeId);

        $ownSources    = $config['own']['sources'] ?? [];
        $otherSources  = $config['other']['sources'] ?? [];
        $customSources = $config['custom']['sources'] ?? [];

        $collection = $this->sourceItemCollection->create();
        $collection->addFieldToFilter('sku', $sku);
        $collection->addFieldToFilter('quantity', ['gt' => 0]); // Only get items with quantity > 0
        $collection->addFieldToFilter('status', 1); // Only enabled sources

        $foundOwn = null;
        $foundOther = null;
        $foundCustom = null;

        foreach ($collection->getData() as $row) {
            $sourceCode = $row['source_code'] ?? '';

            if (in_array($sourceCode, $ownSources, true)) {
                $foundOwn = $sourceCode;
                break; // Own source has highest priority, break early
            } elseif (in_array($sourceCode, $otherSources, true)) {
                $foundOther = $sourceCode;
            } elseif (in_array($sourceCode, $customSources, true)) {
                $foundCustom = $sourceCode;
            }
        }

        return $foundOwn ?? $foundOther ?? $foundCustom;
    }


    /**
     * Identify group for the source
     */
    protected function getSourceGroupBySourceCode(?string $sourceCode, array $config): string
    {
        if (!$sourceCode) {
            return 'other'; // fallback
        }

        foreach ($config as $group => $data) {
            if (in_array($sourceCode, $data['sources'])) {
                return $group;
            }
        }

        return 'other';
    }

    /**
     * Load all config & cache for faster future calls
     */
    protected function getCachedConfig(?int $storeId = null): array
    {
        $storeId = (int)($storeId ?? $this->storeManager->getStore()->getId());

        if ($this->isStorePartner($storeId)) {
            // Partner store always reads live, per-store config and is never
            // cached, so admins can null out own/other and rely solely on
            // custom source without waiting on a stale cache entry.
            return $this->buildSourceConfig($storeId);
        }

        // Use in-memory cache first for better performance.
        // Keyed by store id - a single request/process can touch more than
        // one store scope (e.g. bulk cache population across stores), so a
        // single shared value would leak one store's config into another.
        if (isset($this->configCache[$storeId])) {
            return $this->configCache[$storeId];
        }

        // Try to get from Magento cache - key MUST include the store id,
        // otherwise whichever store populates the cache first "wins" and
        // every other store silently gets served that store's config for
        // the full cache lifetime.
        $cacheKey = self::CACHE_KEY . '_store_' . $storeId;
        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            $this->configCache[$storeId] = json_decode($cached, true);
            return $this->configCache[$storeId];
        }

        $config = $this->buildSourceConfig($storeId);

        // Save to both in-memory and Magento cache, both store-scoped
        $this->configCache[$storeId] = $config;
        $this->cache->save(json_encode($config), $cacheKey, [], 3600); // 1 hour cache

        return $config;
    }

    /**
     * Build the own/other/custom source config strictly from the given store's scope
     */
    private function buildSourceConfig(int $storeId): array
    {
        return [
            'own' => [
                'sources'  => $this->getConfigArray('artm_estimatedelivery/own_source/all_source', $storeId),
                'dispatch' => $this->getConfigValue('artm_estimatedelivery/own_source/dispatch_time', $storeId),
                'delivery' => $this->getConfigValue('artm_estimatedelivery/own_source/delivery_time', $storeId),
            ],
            'other' => [
                'sources'  => $this->getConfigArray('artm_estimatedelivery/other_source/all_source', $storeId),
                'dispatch' => $this->getConfigValue('artm_estimatedelivery/other_source/dispatch_time', $storeId),
                'delivery' => $this->getConfigValue('artm_estimatedelivery/other_source/delivery_time', $storeId),
            ],
            'custom' => [
                'sources'  => $this->getConfigArray('artm_estimatedelivery/custom_source/all_source', $storeId),
                'dispatch' => $this->getConfigValue('artm_estimatedelivery/custom_source/dispatch_time', $storeId),
                'delivery' => $this->getConfigValue('artm_estimatedelivery/custom_source/delivery_time', $storeId),
            ],
        ];
    }

    /**
     * Get comma-separated config as array for current store
     */
    protected function getConfigArray(string $path, ?int $storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId ?? $this->storeManager->getStore()->getId()
        );

        if (empty($value)) {
            return [];
        }

        return array_map('trim', explode(',', $value));
    }

    /**
     * Get config value for the given store scope (defaults to current store)
     */
    protected function getConfigValue(string $path, ?int $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId ?? $this->storeManager->getStore()->getId()
        );
    }

    public function IsEnabled(?int $storeId = null)
    {
        // Cache the enabled status per store id to avoid repeated config calls
        // without leaking one store's enabled/disabled status into another.
        $storeId = (int)($storeId ?? $this->storeManager->getStore()->getId());

        if (!isset($this->enabledStatusCache[$storeId])) {
            $this->enabledStatusCache[$storeId] = (bool)$this->getConfigValue(
                'artm_estimatedelivery/general/enabled',
                $storeId
            );
        }
        return $this->enabledStatusCache[$storeId];
    }

    /**
     * Get cached delivery data for product (FAST METHOD)
     */
    public function getCachedDeliveryData($productId, ?int $storeId = null): ?array
    {
        if (!$this->IsEnabled($storeId) || $this->isStorePartner($storeId)) {
            return null;
        }

        $cachedData = $this->deliveryCacheResource->getCacheByProductId($productId);

        if ($cachedData) {
            $cacheModel = $this->deliveryCacheFactory->create();
            $cacheModel->setData($cachedData);
            
            if ($cacheModel->isCacheValid()) {
                // Convert to expected format
                return [
                    'dispatch_days' => $cachedData['dispatch_days'],
                    'delivery_days' => $cachedData['delivery_days'], 
                    'total_days' => $cachedData['total_days'],
                    'express_shipping' => (bool)$cachedData['express_shipping'],
                    'min_delivery_days' => $cachedData['min_delivery_days'],
                    'max_delivery_days' => $cachedData['max_delivery_days'],
                    'product_type' => $cachedData['product_type'],
                    'simple_product_ids' => $cachedData['simple_product_ids'] ? json_decode($cachedData['simple_product_ids'], true) : [],
                    // This persisted cache is only ever read/valid for non-partner
                    // stores (isStorePartner() forces invalidation below), so a
                    // row existing here always means "display it".
                    'show_estimation' => true
                ];
            } else {
                // Cache is expired - invalidate it so it gets recalculated
                $this->deliveryCacheResource->invalidateCacheForProduct($productId);
            }
        }
        
        return null;
    }

    /**
     * Check if current store is Partner store
     *
     * @return bool
     */
    public function isStorePartner(?int $storeId = null)
    {
        if ($storeId !== null) {
            try {
                return (int)$this->storeManager->getStore($storeId)->getWebsiteId() == self::PARTNER_STORE_ID;
            } catch (\Exception $e) {
                return false;
            }
        }

        return $this->storeManager->getWebsite()->getId() == self::PARTNER_STORE_ID;
    }

    /**
     * Store delivery data in cache
     */
    public function saveCacheData($productId, $sku, $productType, $data, $simpleIds = null): void
    {
        if($this->isStorePartner()) {
            return;
        }

        $cacheModel = $this->deliveryCacheFactory->create();
        
        // First, delete any existing entries for this product to avoid duplicates
        $this->deliveryCacheResource->deleteByProductId($productId);
        
        // Create fresh cache entry
        $cacheModel = $this->deliveryCacheFactory->create();

        $cacheModel->setData([
            'product_id' => $productId,
            'product_type' => $productType,
            'sku' => $sku,
            'simple_product_ids' => $simpleIds ? json_encode($simpleIds) : null,
            'dispatch_days' => $data['dispatch_days'] ?? null,
            'delivery_days' => $data['delivery_days'] ?? null,
            'total_days' => $data['total_days'],
            'express_shipping' => $data['express_shipping'] ? 1 : 0,
            'min_delivery_days' => $data['min_delivery_days'] ?? null,
            'max_delivery_days' => $data['max_delivery_days'] ?? null,
            'calculated_at' => date('Y-m-d H:i:s'),
            'is_valid' => 1
        ]);

        $this->deliveryCacheResource->save($cacheModel);
    }

    /**
     * Calculate and cache delivery data for simple and configurable products only
     */
    public function calculateAndCacheDelivery($productId): ?array
    {
        try {
            $product = $this->productRepository->getById($productId);
            $sku = $product->getSku();
            $productType = $product->getTypeId();
            
            // Only process simple and configurable products
            if (!in_array($productType, ['simple', 'configurable'])) {
                return null; // Skip gift vouchers, virtual, downloadable, etc.
            }

            if ($productType == Type::TYPE_SIMPLE) {
                // Simple product - check if cache already exists first.
                // Skip this read entirely for the Partner store: rows in the
                // DeliveryCache table are only ever written for non-partner
                // stores (see saveCacheData()), so any row found here would
                // be stale data calculated under a different store's config.
                $existingCache = $this->isStorePartner()
                    ? null
                    : $this->deliveryCacheResource->getCacheByProductId($productId);
                
                if ($existingCache && $existingCache['is_valid']) {
                    // Check if cache is still valid (less than 2 days old)
                    $calculatedAt = new \DateTime($existingCache['calculated_at']);
                    $now = new \DateTime();
                    $diff = $now->diff($calculatedAt);
                    
                    if ($diff->days < 2) {
                        // Return existing valid cache data
                        // (this row only exists for non-partner stores - see saveCacheData())
                        return [
                            'dispatch_days' => (int)$existingCache['dispatch_days'],
                            'delivery_days' => (int)$existingCache['delivery_days'],
                            'total_days' => (int)$existingCache['total_days'],
                            'express_shipping' => (bool)$existingCache['express_shipping'],
                            'min_delivery_days' => $existingCache['min_delivery_days'],
                            'max_delivery_days' => $existingCache['max_delivery_days'],
                            'show_estimation' => true
                        ];
                    }
                }
                
                // Calculate new data only if no valid cache exists
                $deliveryData = $this->getEstimatedDeliveryDate($sku);
                
                $cacheData = [
                    'dispatch_days' => $deliveryData['dispatch_days'],
                    'delivery_days' => $deliveryData['delivery_days'],
                    'total_days' => $deliveryData['dispatch_days'] + $deliveryData['delivery_days'],
                    'express_shipping' => $deliveryData['express_shipping'],
                    'min_delivery_days' => null,
                    'max_delivery_days' => null,
                    'show_estimation' => $deliveryData['show_estimation'] ?? true
                ];

                try {
                    $this->saveCacheData($productId, $sku, 'simple', $cacheData);
                } catch (\Exception $saveException) {
                    // Continue even if save fails
                }
                return $cacheData;

            } else if ($productType == 'configurable') {
                // Configurable product - find minimum delivery time from all children
                try {
                    // Check if configurable cache already exists and is valid.
                    // Skip this read for the Partner store, same reasoning as
                    // the simple-product branch above.
                    $existingConfigCache = $this->isStorePartner()
                        ? null
                        : $this->deliveryCacheResource->getCacheByProductId($productId);
                    $configurableHasValidCache = false;
                    
                    if ($existingConfigCache && $existingConfigCache['is_valid'] && $this->isCacheValid($existingConfigCache['calculated_at'])) {
                        $configurableHasValidCache = true;
                    }
                    
                    // Get child products to check their cache status too
                    $childProducts = $product->getTypeInstance()->getUsedProducts($product);
                    if (empty($childProducts)) {
                        // No child products, treat as simple
                        $deliveryData = $this->getEstimatedDeliveryDate($sku);
                        $fallbackCacheData = [
                            'dispatch_days' => $deliveryData['dispatch_days'],
                            'delivery_days' => $deliveryData['delivery_days'],
                            'total_days' => $deliveryData['total_days'],
                            'express_shipping' => $deliveryData['express_shipping'],
                            'min_delivery_days' => null,
                            'max_delivery_days' => null,
                            'show_estimation' => $deliveryData['show_estimation'] ?? true
                        ];
                        try {
                            $this->saveCacheData($productId, $sku, 'configurable', $fallbackCacheData);
                        } catch (\Exception $fallbackSaveException) {
                            // Continue even if save fails
                        }
                        return $fallbackCacheData;
                    }
                    
                    // If configurable has valid cache, also check if ALL child simple products have valid cache
                    $allChildrenHaveValidCache = true;
                    if ($configurableHasValidCache) {
                        $childIds = array_map(function($child) { return $child->getId(); }, $childProducts);
                        $childCache = $this->deliveryCacheResource->getCacheByProductIds($childIds);
                        
                        // Check if all children have valid cache
                        $childCacheMap = [];
                        foreach ($childCache as $cache) {
                            if ($cache['is_valid'] && $this->isCacheValid($cache['calculated_at'])) {
                                $childCacheMap[$cache['product_id']] = true;
                            }
                        }
                        
                        // Ensure every child has valid cache
                        foreach ($childIds as $childId) {
                            if (!isset($childCacheMap[$childId])) {
                                $allChildrenHaveValidCache = false;
                                break;
                            }
                        }
                        
                        // Only return early if BOTH configurable AND all children have valid cache
                        if ($allChildrenHaveValidCache) {
                            // This row only exists for non-partner stores - see saveCacheData()
                            return [
                                'dispatch_days' => (int)$existingConfigCache['dispatch_days'],
                                'delivery_days' => (int)$existingConfigCache['delivery_days'],
                                'total_days' => (int)$existingConfigCache['total_days'],
                                'express_shipping' => (bool)$existingConfigCache['express_shipping'],
                                'min_delivery_days' => $existingConfigCache['min_delivery_days'],
                                'max_delivery_days' => $existingConfigCache['max_delivery_days'],
                                'show_estimation' => true
                            ];
                        }
                    }
                    
                    // Get child product IDs for batch cache checking (recalculation needed).
                    // Skipped for the Partner store - same reasoning as above.
                    $childIds = array_map(function($child) { return $child->getId(); }, $childProducts);
                    $childCache = $this->isStorePartner()
                        ? []
                        : $this->deliveryCacheResource->getCacheByProductIds($childIds);
                    
                    // Index cache by product ID for faster lookup and invalidate expired ones
                    $childCacheData = [];
                    foreach ($childCache as $cache) {
                        if ($cache['is_valid'] && $this->isCacheValid($cache['calculated_at'])) {
                            $childCacheData[$cache['product_id']] = $cache;
                        } else {
                            // Invalidate expired child cache
                            $this->deliveryCacheResource->invalidateCacheForProduct($cache['product_id']);
                        }
                    }
                    
                    $allDeliveryData = [];
                    $hasExpressChild = false;

                    foreach ($childProducts as $childProduct) {
                        try {
                            $childId = $childProduct->getId();
                            
                            if (isset($childCacheData[$childId])) {
                                // Use existing cache data (only exists for non-partner
                                // stores, so this child is always visible)
                                $existingCache = $childCacheData[$childId];
                                $childTotalDays = (int)$existingCache['total_days'];
                                $childExpressShipping = (bool)$existingCache['express_shipping'];
                                
                                $allDeliveryData[] = [
                                    'dispatch_days' => (int)$existingCache['dispatch_days'],
                                    'delivery_days' => (int)$existingCache['delivery_days'],
                                    'total_days' => $childTotalDays,
                                    'express_shipping' => $childExpressShipping,
                                    'show_estimation' => true
                                ];
                                
                                if ($childExpressShipping) {
                                    $hasExpressChild = true;
                                }
                            } else {
                                // Calculate new data for simple product
                                $childDelivery = $this->getEstimatedDeliveryDate($childProduct->getSku());
                                $childTotalDays = $childDelivery['dispatch_days'] + $childDelivery['delivery_days'];
                                $childShowEstimation = $childDelivery['show_estimation'] ?? true;
                                
                                $allDeliveryData[] = [
                                    'dispatch_days' => $childDelivery['dispatch_days'],
                                    'delivery_days' => $childDelivery['delivery_days'],
                                    'total_days' => $childTotalDays,
                                    'express_shipping' => $childDelivery['express_shipping'],
                                    'show_estimation' => $childShowEstimation
                                ];
                                
                                if ($childDelivery['express_shipping']) {
                                    $hasExpressChild = true;
                                }

                                // Save individual simple product only if no valid cache exists
                                // (no-op for partner store - see saveCacheData())
                                $childCacheDataToSave = [
                                    'dispatch_days' => $childDelivery['dispatch_days'],
                                    'delivery_days' => $childDelivery['delivery_days'], 
                                    'total_days' => $childTotalDays,
                                    'express_shipping' => $childDelivery['express_shipping'],
                                    'min_delivery_days' => null,
                                    'max_delivery_days' => null,
                                    'show_estimation' => $childShowEstimation
                                ];
                                try {
                                    $this->saveCacheData($childProduct->getId(), $childProduct->getSku(), 'simple', $childCacheDataToSave);
                                } catch (\Exception $childSaveException) {
                                    // Continue processing other children even if one fails to save
                                }
                            }
                            
                        } catch (\Exception $childException) {
                            // Skip this child if processing fails, continue with others
                            continue;
                        }
                    }

                    // Only consider children whose source actually resolved to a
                    // configured group when picking min/max delivery - a hidden
                    // child (e.g. partner store, source not configured) must not
                    // leak its dispatch/delivery numbers into the displayed range.
                    $visibleDeliveryData = array_values(array_filter(
                        $allDeliveryData,
                        function ($option) {
                            return $option['show_estimation'] ?? true;
                        }
                    ));

                    $overallShowEstimation = !empty($visibleDeliveryData);
                    $allDeliveryData = $visibleDeliveryData;

                    if (!empty($allDeliveryData)) {
                        // Find the delivery option with minimum total days
                        $minTotalDays = min(array_column($allDeliveryData, 'total_days'));
                        $minDeliveryOption = null;
                        
                        foreach ($allDeliveryData as $deliveryOption) {
                            if ($deliveryOption['total_days'] === $minTotalDays) {
                                $minDeliveryOption = $deliveryOption;
                                break;
                            }
                        }
                        
                        $maxDays = max(array_column($allDeliveryData, 'total_days'));

                        $configurableCacheData = [
                            'dispatch_days' => $minDeliveryOption['dispatch_days'], // Use minimum option's dispatch days
                            'delivery_days' => $minDeliveryOption['delivery_days'],   // Use minimum option's delivery days  
                            'total_days' => $minDeliveryOption['total_days'],        // Minimum total days
                            'express_shipping' => $hasExpressChild,                  // True if any child has express
                            'min_delivery_days' => $minTotalDays,
                            'max_delivery_days' => $maxDays,
                            'show_estimation' => $overallShowEstimation
                        ];

                        try {
                            $this->saveCacheData($productId, $sku, 'configurable', $configurableCacheData, $childIds);
                        } catch (\Exception $configSaveException) {
                            // Continue even if save fails
                        }
                        return $configurableCacheData;
                    } else {
                        // No child had a visible/matching estimation (e.g. partner
                        // store with no configured source match on any variant) -
                        // do not fall back to the parent SKU's own estimation here,
                        // since that could re-introduce an attribute-based fallback
                        // that partner stores should not display.
                        if ($this->isStorePartner()) {
                            return [
                                'dispatch_days' => 0,
                                'delivery_days' => 0,
                                'total_days' => 0,
                                'express_shipping' => false,
                                'min_delivery_days' => null,
                                'max_delivery_days' => null,
                                'show_estimation' => false
                            ];
                        }

                        // No child products processed successfully, fallback to parent SKU
                        $deliveryData = $this->getEstimatedDeliveryDate($sku);
                        $fallbackCacheData = [
                            'dispatch_days' => $deliveryData['dispatch_days'],
                            'delivery_days' => $deliveryData['delivery_days'],
                            'total_days' => $deliveryData['total_days'],
                            'express_shipping' => $deliveryData['express_shipping'],
                            'min_delivery_days' => null,
                            'max_delivery_days' => null,
                            'show_estimation' => $deliveryData['show_estimation'] ?? true
                        ];
                        try {
                            $this->saveCacheData($productId, $sku, 'configurable', $fallbackCacheData);
                        } catch (\Exception $fallbackSaveException) {
                            // Continue even if save fails
                        }
                        return $fallbackCacheData;
                    }
                } catch (\Exception $configurableException) {
                    // If configurable processing completely fails, treat as simple
                    $deliveryData = $this->getEstimatedDeliveryDate($sku);
                    $fallbackCacheData = [
                        'dispatch_days' => $deliveryData['dispatch_days'],
                        'delivery_days' => $deliveryData['delivery_days'],
                        'total_days' => $deliveryData['total_days'],
                        'express_shipping' => $deliveryData['express_shipping'],
                        'min_delivery_days' => null,
                        'max_delivery_days' => null,
                        'show_estimation' => $deliveryData['show_estimation'] ?? true
                    ];
                    try {
                        $this->saveCacheData($productId, $sku, $productType, $fallbackCacheData);
                    } catch (\Exception $saveFallbackException) {
                        // Continue even if save fails
                    }
                    return $fallbackCacheData;
                }
            }

        } catch (\Exception $e) {
            // Log error but don't break the flow
            $this->_logger->error('EstimateDelivery cache calculation failed for product ID ' . $productId . ': ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Process delivery data for bulk SKUs (for category/listing pages)
     * Returns array with express delivery flags
     */
    public function processDeliveryForSkus(array $skus, array $skuToProductMap = []): array
    {
        $expressDeliveryResults = [];
        
        if (!$this->IsEnabled()) {
            // If module disabled, return false for all
            foreach ($skus as $sku) {
                $expressDeliveryResults[$sku] = false;
            }
            return $expressDeliveryResults;
        }

        // The persisted DeliveryCache table is only ever written for
        // non-partner stores (see saveCacheData()/getCachedDeliveryData()).
        // A row found here for the Partner store would be stale data
        // calculated under a different store's config, so skip the direct
        // cache read entirely for it and always recalculate live below.
        $isPartnerStore = $this->isStorePartner();

        foreach ($skus as $sku) {
            $isExpress = false;
            
            try {
                // Check if cache record exists (skipped for Partner store)
                $cacheData = $isPartnerStore ? null : $this->deliveryCacheResource->getCacheBySku($sku);
                
                if ($cacheData && isset($cacheData['express_shipping'])) {
                    // Check if cache is valid (less than 2 days old)
                    if ($cacheData['is_valid'] && $this->isCacheValid($cacheData['calculated_at'])) {
                        // Older cache rows may not carry a show_estimation column -
                        // treat missing as visible (legacy/pre-existing behavior).
                        $showEstimation = !array_key_exists('show_estimation', $cacheData) || $cacheData['show_estimation'];
                        // Use cached value, but never report express for a
                        // product whose source doesn't match a configured group
                        $isExpress = $showEstimation ? (bool)$cacheData['express_shipping'] : false;
                    } else {
                        // Cache is expired - invalidate and recalculate
                        if (isset($cacheData['product_id'])) {
                            $this->deliveryCacheResource->invalidateCacheForProduct($cacheData['product_id']);
                        }
                        
                        // Trigger recalculation
                        $productId = isset($skuToProductMap[$sku]) ? $skuToProductMap[$sku] : (isset($cacheData['product_id']) ? $cacheData['product_id'] : null);
                        
                        if ($productId) {
                            try {
                                // Check product type first to exclude unsupported types
                                $product = $this->productRepository->getById($productId);
                                $productType = $product->getTypeId();
                                
                                if (!in_array($productType, ['simple', 'configurable'])) {
                                    $expressDeliveryResults[$sku] = false;
                                    continue;
                                }
                                
                                // Use centralized calculation method
                                $calculatedData = $this->calculateAndCacheDelivery($productId);
                                $isExpress = ($calculatedData && ($calculatedData['show_estimation'] ?? true))
                                    ? (bool)$calculatedData['express_shipping']
                                    : false;
                            } catch (\Exception $recalcException) {
                                $isExpress = false;
                            }
                        } else {
                            $isExpress = false;
                        }
                    }
                } else {
                    // Need to calculate and cache (also entered directly for
                    // Partner store, since $cacheData is always null there)
                    $productId = isset($skuToProductMap[$sku]) ? $skuToProductMap[$sku] : null;
                    
                    if ($productId) {
                        // Check product type first to exclude unsupported types
                        try {
                            $product = $this->productRepository->getById($productId);
                            $productType = $product->getTypeId();
                            
                            // Only process simple and configurable products
                            if (!in_array($productType, ['simple', 'configurable'])) {
                                $expressDeliveryResults[$sku] = false;
                                continue; // Skip to next SKU
                            }
                        } catch (\Exception $typeCheckException) {
                            $expressDeliveryResults[$sku] = false;
                            continue;
                        }
                        
                        try {
                            // Use centralized calculation method
                            // (no-op cache write for Partner store - see saveCacheData())
                            $calculatedData = $this->calculateAndCacheDelivery($productId);
                            $isExpress = ($calculatedData && ($calculatedData['show_estimation'] ?? true))
                                ? (bool)$calculatedData['express_shipping']
                                : false;
                        } catch (\Exception $calcException) {
                            // If calculation fails, try direct SKU method and manual cache save
                            try {
                                $deliveryData = $this->getEstimatedDeliveryDate($sku);
                                $isExpress = ($deliveryData['show_estimation'] ?? true)
                                    ? (bool)$deliveryData['express_shipping']
                                    : false;
                                
                                // Try to save simple cache record manually
                                // (no-op for Partner store - see saveCacheData())
                                $this->saveCacheData($productId, $sku, 'simple', [
                                    'dispatch_days' => $deliveryData['dispatch_days'],
                                    'delivery_days' => $deliveryData['delivery_days'],
                                    'total_days' => $deliveryData['total_days'],
                                    'express_shipping' => $deliveryData['express_shipping'],
                                    'min_delivery_days' => null,
                                    'max_delivery_days' => null,
                                    'show_estimation' => $deliveryData['show_estimation'] ?? true
                                ]);
                            } catch (\Exception $fallbackException) {
                                // Last resort - just return false but continue processing
                                $isExpress = false;
                            }
                        }
                    } else {
                        // Fallback: calculate directly using SKU (no cache save without product ID)
                        try {
                            $deliveryData = $this->getEstimatedDeliveryDate($sku);
                            $isExpress = ($deliveryData['show_estimation'] ?? true)
                                ? (bool)$deliveryData['express_shipping']
                                : false;
                        } catch (\Exception $skuException) {
                            $isExpress = false;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error and default to false
                $this->_logger->error('ProcessDeliveryForSkus failed for SKU ' . $sku . ': ' . $e->getMessage());
                $isExpress = false;
            }
            
            $expressDeliveryResults[$sku] = $isExpress;
        }
        
        return $expressDeliveryResults;
    }

    /**
     * Check if cache is valid (less than 2 days old)
     */
    private function isCacheValid($calculatedAt): bool
    {
        try {
            $calculatedAtTime = new \DateTime($calculatedAt);
            $now = new \DateTime();
            $diff = $now->diff($calculatedAtTime);
            return $diff->days < 2;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get product source with caching to improve performance
     */
    public function getProductSourceCached($sku, ?int $storeId = null)
    {
        // Cache key includes store id: which group (own/other/custom) a source
        // belongs to depends on per-store config, so the same SKU can resolve
        // to a different matched source/group depending on the current store.
        $storeId = (int)($storeId ?? $this->storeManager->getStore()->getId());
        $cacheKey = $storeId . ':' . $sku;

        if (isset($this->productSourceCache[$cacheKey])) {
            return $this->productSourceCache[$cacheKey];
        }
        
        $source = $this->calculateProductSource($sku, $storeId);
        $this->productSourceCache[$cacheKey] = $source;
        
        return $source;
    }
    
    /**
     * Clear all in-memory caches (useful for long-running processes)
     */
    public function clearInMemoryCache()
    {
        $this->productSourceCache = [];
        $this->configCache = [];
        $this->enabledStatusCache = [];
    }
}
