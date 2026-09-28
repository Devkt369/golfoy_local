<?php

namespace Wizzy\MSI\Services\Catalogue;

use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Wizzy\Search\Services\Store\StoreCatalogueConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ProductInventoryManager
{
    /**
     * @var SourceItemRepositoryInterface
     */
    protected $sourceItems;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    private $storeCatalogueConfig;
    /**
     * @param SourceItemRepositoryInterface $sourceItems
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItems,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreCatalogueConfig $storeCatalogueConfig
    ) {
        $this->sourceItems = $sourceItems;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeCatalogueConfig = $storeCatalogueConfig;
    }

    public function getData($sku)
    {
        $data = NULL;
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $sku)->create();
        $sourceItemData = $this->sourceItems->getList($searchCriteria);
        $maxStock = 0;
        $isInStock = false;

        if ($sourceItemData->getItems()) {
            foreach ($sourceItemData->getItems() as $sourceItem) {
                $sourceStock = $sourceItem->getQuantity();

                if ($maxStock == 0) {
                    $maxStock = $sourceStock;
                }
                if ($sourceStock > $maxStock && $sourceItem->getStatus() == 1) {
                    $maxStock = $sourceStock;
                }
                if ($sourceItem->getStatus() == 1 && $sourceStock >= 1) {
                    $isInStock = true;
                }
            }
            $data =
                [
                    'inStock' => $isInStock,
                    'qty' => ($maxStock < 0) ? 0 : $maxStock
                ];
        }

        return $data;
    }

    public function getProductSKU($product)
    {
        $attributes = $product['attributes'];
        foreach ($attributes as $index => $attribute) {
            if ($attribute['id'] == "sku") {
                foreach ($attribute['values'] as $skuCode) {
                    if ($skuCode['variationId'] == $product['id']) {
                        return $skuCode['value'][0];
                    }
                }
            }
        }
        return false;
    }
}
