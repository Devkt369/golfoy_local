<?php

namespace ArtM\EstimateDelivery\Model;

use ArtM\EstimateDelivery\Helper\Data as EstimateHelper;
use ArtM\EstimateDelivery\Model\ResourceModel\DeliveryCache as DeliveryCacheResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class EstimateCalculator
{
    /**
     * @var EstimateHelper
     */
    protected $estimateHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DeliveryCacheResource
     */
    protected $deliveryCacheResource;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Constructor
     *
     * @param EstimateHelper $estimateHelper
     * @param DeliveryCacheResource $deliveryCacheResource
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        EstimateHelper $estimateHelper,
        DeliveryCacheResource $deliveryCacheResource,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->estimateHelper = $estimateHelper;
        $this->deliveryCacheResource = $deliveryCacheResource;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Get estimated ship and delivery dates for order products
     * Returns the latest dates among all products in the order
     * Uses batch cache fetching for optimal performance
     *
     * @param array $productIds
     * @return array
     */
    public function getEstimatedDates(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        try {
            $maxTotalDays = 0;
            $minTotalDays = PHP_INT_MAX;

            // Get cached data for all products in one query (batch fetch)
            $cachedResults = $this->deliveryCacheResource->getCacheByProductIds($productIds);
            $validCachedProductIds = [];
            $expiredCacheProductIds = [];

            foreach ($cachedResults as $cachedData) {
                // Check if cache is valid (less than 2 days old)
                $timezone = new \DateTimeZone('Asia/Kolkata');
                $calculatedAt = new \DateTime($cachedData['calculated_at'], $timezone);
                $now = new \DateTime('now', $timezone);
                $diff = $now->diff($calculatedAt);
                
                if ($diff->days < 2 && $cachedData['is_valid']) {
                    $validCachedProductIds[] = $cachedData['product_id'];
                    $totalDays = (int)$cachedData['total_days'];
                    
                    // Track both minimum and maximum delivery times (only total_days)
                    if ($totalDays > $maxTotalDays) {
                        $maxTotalDays = $totalDays;
                    }
                    if ($totalDays < $minTotalDays) {
                        $minTotalDays = $totalDays;
                    }
                    
                } else {
                    // Cache is expired or invalid - mark for recalculation
                    $expiredCacheProductIds[] = $cachedData['product_id'];
                    $this->deliveryCacheResource->invalidateCacheForProduct($cachedData['product_id']);
                }
            }

            // Handle missing cache (no cache found) AND expired cache (needs recalculation)
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
                            $this->logger->info(
                                'EstimateCalculator: Skipping non-physical product type ' . $productType . ' for product ID ' . $productId
                            );
                            continue; // Skip gift vouchers, virtual, downloadable, etc.
                        }
                        
                        // Use centralized calculation method to create cache entries
                        $calculatedData = $this->estimateHelper->calculateAndCacheDelivery($productId);
                        
                        if ($calculatedData && isset($calculatedData['total_days'])) {
                            $totalDays = (int)$calculatedData['total_days'];
                            
                            // Track both minimum and maximum delivery times (only total_days)
                            if ($totalDays > $maxTotalDays) {
                                $maxTotalDays = $totalDays;
                            }
                            if ($totalDays < $minTotalDays) {
                                $minTotalDays = $totalDays;
                            }
                            
                        }
                    } catch (\Exception $productException) {
                        // Log error but continue with other products
                        $this->logger->error(
                            'EstimateCalculator: Calculation failed for product ID ' . $productId . ': ' . $productException->getMessage()
                        );
                        continue;
                    }
                }
            }

            // Calculate dates based on minimum and maximum days (using total_days only)
            if ($maxTotalDays > 0) {
                // Use India timezone for date calculations
                $timezone = new \DateTimeZone('Asia/Kolkata');
                $currentDate = new \DateTime('now', $timezone);
                
                // Calculate EARLY delivery date (minimum total_days)
                $deliveryDateEarly = clone $currentDate;
                $deliveryDateEarly->modify("+{$minTotalDays} days");
                
                // Calculate LAST delivery date (maximum total_days)
                $deliveryDateLast = clone $currentDate;
                $deliveryDateLast->modify("+{$maxTotalDays} days");
                
                return [
                    'estimated_delivery_early' => $deliveryDateEarly->format('Y-m-d'),
                    'estimated_delivery_last' => $deliveryDateLast->format('Y-m-d')
                ];
            }

            return [];

        } catch (\Exception $e) {
            $this->logger->error(
                'EstimateCalculator: Failed to get estimated dates: ' . $e->getMessage()
            );
            return [];
        }
    }

    /**
     * Get estimated delivery date for a single product by SKU
     *
     * @param string $sku
     * @return array
     */
    public function getEstimatedDateBySku(string $sku): array
    {
        try {
            return $this->estimateHelper->getEstimatedDeliveryDate($sku);
        } catch (\Exception $e) {
            $this->logger->error(
                'EstimateCalculator: Failed to get estimated date for SKU ' . $sku . ': ' . $e->getMessage()
            );
            return [];
        }
    }
}
