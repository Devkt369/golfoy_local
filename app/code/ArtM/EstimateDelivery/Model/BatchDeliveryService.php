<?php

namespace ArtM\EstimateDelivery\Model;

use ArtM\EstimateDelivery\Helper\Data;
use ArtM\EstimateDelivery\Model\ResourceModel\DeliveryCache as DeliveryCacheResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class BatchDeliveryService
{
    protected $helper;
    protected $deliveryCacheResource;
    protected $productRepository;
    protected $logger;

    public function __construct(
        Data $helper,
        DeliveryCacheResource $deliveryCacheResource,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->deliveryCacheResource = $deliveryCacheResource;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Uses the same logic as GetBatchDelivery controller.
     *
     * @param array $productIds
     * @return array
     */
    public function getBatchDelivery(array $productIds): array
    {
        $results = [];
        $deliveryDates = [];

        if (empty($productIds)) {
            return $results;
        }

        // The bulk cache lookup below reads the DeliveryCache table directly,
        // bypassing Data::getCachedDeliveryData()'s isStorePartner() invalidation.
        // Rows in that table are only ever written for non-partner stores (see
        // Data::saveCacheData()), so for the Partner store any row found here
        // would be stale data calculated under a different store's config.
        // Skip the bulk cache path entirely for the Partner store and let every
        // product go through the "missing" branch below, which recalculates
        // live via the helper (store-scope aware, and Partner-store aware of
        // which sources are actually configured).
        $isPartnerStore = $this->helper->isStorePartner();

        $validCachedProductIds = [];
        $expiredCacheProductIds = [];

        if (!$isPartnerStore) {
            // Get cached data for all products in one query
            $cachedResults = $this->deliveryCacheResource->getCacheByProductIds($productIds);

            foreach ($cachedResults as $cachedData) {
                // Check if cache is valid (less than 2 days old)
                $calculatedAt = new \DateTime($cachedData['calculated_at']);
                $now = new \DateTime();
                $diff = $now->diff($calculatedAt);

                if ($diff->days < 2 && $cachedData['is_valid']) {
                    $validCachedProductIds[] = $cachedData['product_id'];

                    // Older cache rows may not carry a show_estimation column -
                    // treat missing as visible (legacy/pre-existing behavior).
                    $showEstimation = !array_key_exists('show_estimation', $cachedData) || $cachedData['show_estimation'];

                    if ($showEstimation) {
                        $totalDays = (int)$cachedData['total_days'];

                        // Calculate delivery date
                        $deliveryDate = new \DateTime();
                        $deliveryDate->modify("+{$totalDays} days");
                        $deliveryDateStr = $deliveryDate->format('Y-m-d');

                        $results[$cachedData['product_id']] = [
                            'product_id' => $cachedData['product_id'],
                            'sku' => $cachedData['sku'],
                            'total_days' => $totalDays,
                            'estimated_delivery_date' => $deliveryDateStr,
                            'express_shipping' => (bool)$cachedData['express_shipping'],
                            'show_estimation' => true,
                            'cached' => true
                        ];

                        $deliveryDates[] = $deliveryDateStr;
                    }
                    // else: source doesn't match a configured group - this
                    // product contributes no result and no date, same as it
                    // being hidden on the PDP/cart page.
                } else {
                    // Cache is expired or invalid - mark for recalculation
                    $expiredCacheProductIds[] = $cachedData['product_id'];
                    $this->deliveryCacheResource->invalidateCacheForProduct($cachedData['product_id']);
                }
            }
        }

        // Handle missing cache (no cache found) AND expired cache (needs recalculation)
        // For the Partner store, $validCachedProductIds stays empty above, so
        // every product ends up here and gets recalculated live.
        $missingProductIds = array_diff($productIds, $validCachedProductIds);

        // Ensure all expired cache products are included for recalculation
        $missingProductIds = array_unique(array_merge($missingProductIds, $expiredCacheProductIds));

        if (!empty($missingProductIds)) {
            foreach ($missingProductIds as $productId) {
                try {
                    // Check product type first - only process simple and configurable
                    $product = $this->productRepository->getById($productId);
                    $productType = $product->getTypeId();

                    if (!in_array($productType, ['simple', 'configurable'])) {
                        continue; // Skip gift vouchers, virtual, downloadable, etc.
                    }

                    // Use centralized calculation method to create cache entries
                    // (for the Partner store this recalculates live from that
                    // store's own/other/custom config and is never persisted -
                    // see Data::calculateAndCacheDelivery()/saveCacheData())
                    $calculatedData = $this->helper->calculateAndCacheDelivery($productId);

                    if ($calculatedData) {
                        $showEstimation = $calculatedData['show_estimation'] ?? true;

                        if (!$showEstimation) {
                            // Source doesn't match a configured group - do not
                            // add a result or a date for this product.
                            continue;
                        }

                        $totalDays = (int)$calculatedData['total_days'];

                        // Calculate delivery date
                        $deliveryDate = new \DateTime();
                        $deliveryDate->modify("+{$totalDays} days");
                        $deliveryDateStr = $deliveryDate->format('Y-m-d');

                        $results[$productId] = [
                            'product_id' => $productId,
                            'total_days' => $totalDays,
                            'estimated_delivery_date' => $deliveryDateStr,
                            'express_shipping' => (bool)$calculatedData['express_shipping'],
                            'show_estimation' => true,
                            'cached' => false // Newly calculated
                        ];

                        $deliveryDates[] = $deliveryDateStr;
                    }
                } catch (\Exception $productException) {
                    // Log error but continue with other products
                    $this->logger->error(
                        'BatchDeliveryService calculation failed for product ID ' .
                        $productId . ': ' . $productException->getMessage()
                    );
                    continue;
                }
            }
        }

        $results['delivery_dates'] = $deliveryDates;

        return $results;
    }
}