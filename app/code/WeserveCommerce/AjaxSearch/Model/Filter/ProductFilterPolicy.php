<?php
namespace WeserveCommerce\AjaxSearch\Model\Filter;

use WeserveCommerce\AjaxSearch\Api\ProductFilterPolicyInterface;
use WeserveCommerce\AjaxSearch\Model\Config;

class ProductFilterPolicy implements ProductFilterPolicyInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StockFilter
     */
    private $stockFilter;

    public function __construct(
        Config $config,
        StockFilter $stockFilter
    ) {
        $this->config = $config;
        $this->stockFilter = $stockFilter;
    }

    /**
     * @param int $storeId
     * @param array $requestFilters
     * @return array
     */
    public function buildFilters(int $storeId, array $requestFilters = []): array
    {
        $filters = [];

        // 1. Status Filter: Only enabled products
        $filters[] = [
            'term' => [
                'status' => 1
            ]
        ];

        // 2. Visibility Filter: Visible in search (3 or 4)
        $filters[] = [
            'terms' => [
                'visibility' => [3, 4]
            ]
        ];

        // 3. Stock Filter
        $shouldHideStock = $this->config->hideOutOfStock($storeId);
        if (isset($requestFilters['in_stock'])) {
            $shouldHideStock = (bool)$requestFilters['in_stock'];
        }
        if ($shouldHideStock) {
            $stockClause = $this->stockFilter->build(true);
            if (!empty($stockClause)) {
                $filters[] = $stockClause;
            }
        }

        // 4. Price Filters (min_price, max_price)
        if (isset($requestFilters['min_price']) || isset($requestFilters['max_price'])) {
            $range = [];
            if (isset($requestFilters['min_price']) && is_numeric($requestFilters['min_price'])) {
                $minPrice = (float)$requestFilters['min_price'];
                // Support both standard decimal price and paise
                $range['gte'] = $minPrice > 1000 ? $minPrice : $minPrice * 100;
            }
            if (isset($requestFilters['max_price']) && is_numeric($requestFilters['max_price'])) {
                $maxPrice = (float)$requestFilters['max_price'];
                $range['lte'] = $maxPrice > 1000 ? $maxPrice : $maxPrice * 100;
            }
            if (!empty($range)) {
                $filters[] = [
                    'range' => [
                        'customprice' => $range
                    ]
                ];
            }
        }

        // 5. Category Filter
        if (!empty($requestFilters['category'])) {
            $catId = (int)$requestFilters['category'];
            if ($catId > 0) {
                $filters[] = [
                    'term' => [
                        'category_ids' => $catId
                    ]
                ];
            }
        }

        // 6. Brand Filter
        if (!empty($requestFilters['brand'])) {
            $filters[] = [
                'term' => [
                    'brand_value' => (string)$requestFilters['brand']
                ]
            ];
        }

        return $filters;
    }
}
