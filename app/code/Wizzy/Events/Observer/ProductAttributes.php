<?php

/**
Copyright © Magento, Inc. All rights reserved.
See COPYING.txt for license details.
 */

namespace Wizzy\Events\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Catalog\Model\ProductFactory;
use Wizzy\Search\Services\Catalogue\Mappers\ProductPrices;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Wizzy\MSI\Services\Catalogue\ProductInventoryManager;

class ProductAttributes implements ObserverInterface
{
    private $productRepository;
    private $searchCriteriaBuilder;
    protected $productFactory;
    protected $productPrices;
    protected $stockRegistry;
    protected $sourceItems;
    private $productInventoryManager; 

    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductFactory $productFactory,
        ProductPrices $productPrices,
        StockRegistry $stockRegistry,
        SourceItemRepositoryInterface $sourceItems,
        ProductInventoryManager $productInventoryManager
    ) {
        $this->productRepository = $productRepository;
        $this->productInventoryManager = $productInventoryManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productFactory = $productFactory;
        $this->productPrices = $productPrices;
        $this->stockRegistry = $stockRegistry;
        $this->sourceItems = $sourceItems;
    }

    public function execute(Observer $observer)
    {
        $data = $observer->getData('data');
        $products = $data->getDataByKey('products');
        $productIds = [];
        $parentProductAttributes = [];

        foreach ($products as $product) {
            $productIds[] = $product['id'];
        }

        foreach ($products as $index => $product) {
            $sku = $this->productInventoryManager->getProductSKU($product);
            if ($this->isParentProduct($product)) {
                $parentInStock = false;
                $parentStockQty = 0;
                $parentProduct = $this->productRepository->get($sku);
                $children = $parentProduct->getTypeInstance()->getUsedProducts($parentProduct);
                foreach ($children as $child) {
                    $childSku = $child->getData('sku');
                    $stockData = $this->productInventoryManager->getData($childSku);
                    if (isset($stockData)) {
                        if ($parentStockQty == 0) {
                            $parentStockQty = $stockData['qty'];
                        }
                        if ($stockData['qty'] > $parentStockQty) {
                            $parentStockQty = $stockData['qty'];
                        }
                        if ($stockData['inStock'] && $stockData['qty'] > 0) {
                            $parentInStock = true;
                        }
                    }
                }
                $stockData = [
                    'inStock' => $parentInStock,
                    'qty' => ($parentStockQty < 0) ? 0 : $parentStockQty,
                ];
            } else {
                $stockData = $this->productInventoryManager->getData($sku);
            }
            if ($stockData !== null) {
                $products[$index]['inStock'] = ($stockData['inStock'] && $stockData['qty'] > 0) ? true : false;
                $products[$index]['stockQty'] = ($stockData['qty'] < 0) ? 0 : $stockData['qty'];
            }
        }

        $bundleProducts = $this->getBundleProducts($productIds);
        $bundleProductIds = [];
        foreach ($bundleProducts as $bundleProduct) {
            $bundleProductIds[] = $bundleProduct->getId();
        }

        $productAttributesData = $this->attributesToBeAdded($productIds);

        foreach ($products as $pIndex => $product) {
            $id = $product['id'];

            if (in_array($id, $bundleProductIds)) {
                foreach ($bundleProducts as $bundleProductId) {
                    $childData = [];
                    $finalPrice = PHP_INT_MAX;
                    $sellingPrice = PHP_INT_MAX;
                    $discount = PHP_INT_MAX;
                    $discountPercetnage = PHP_INT_MAX;
                    $price = PHP_INT_MAX;

                    $highestChildQty = 0;
                    $isAllChildOutOfStock = true;

                    $childProducts = $this->getChildProducts($bundleProductId);

                    foreach ($childProducts as $childProduct) {
                        $childOriginalPrice = $this->productPrices->getOriginalPrice($childProduct);
                        $childSellingPrice = $this->productPrices->getSellingPrice($childProduct);
                        $childFinalPrice = $this->productPrices->getFinalPrice($childProduct);
                        
                        $childStockdetails = NULL;
                        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $childProduct->getSku())->create();
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
                            $childStockdetails =
                                [
                                    'inStock' => $isInStock,
                                    'qty' => $maxStock,
                                ];
                        }else{
                            $childStockdetails = $this->getProductStockDetails($childProduct);
                        }
                        

                        // Calculation of lowest prices
                        if ($finalPrice > $childFinalPrice) {
                            $finalPrice = $childFinalPrice;
                            $sellingPrice = $childSellingPrice;

                            if ($childSellingPrice < $childOriginalPrice) {
                                $discount = ($childOriginalPrice - $childSellingPrice);
                                $discountPercetnage =
                                    round((($childOriginalPrice - $childSellingPrice) / $childOriginalPrice) * 100);
                                $price = $childOriginalPrice;
                            }
                        }

                        // Calculation of higest stock qty
                        if ($highestChildQty < $childStockdetails['qty']) {
                            $highestChildQty = $childStockdetails['qty'];
                        }

                        if ($childStockdetails['inStock'] == true) {
                            $isAllChildOutOfStock = false;
                        }

                    }

                    if ($isAllChildOutOfStock) {
                        $childStockdetails['inStock'] = false;
                    } else {
                        $childStockdetails['inStock'] = true;
                    }

                    $childData = [
                        'originalPrice' => $price,
                        'sellingPrice' => $sellingPrice,
                        'finalPrice' => $finalPrice,
                        'discount' => $discount,
                        'discountPercentage' => $discountPercetnage,
                        'inStock' => $childStockdetails['inStock'],
                        'stockQty' => $highestChildQty
                    ];
                }

                $products[$pIndex]['sellingPrice'] = $childData['sellingPrice'];
                $products[$pIndex]['finalPrice'] = $childData['finalPrice'];
                $products[$pIndex]['price'] = $childData['originalPrice'];
                $products[$pIndex]['discount'] = $childData['discount'];
                $products[$pIndex]['discountPercentage'] = $childData['discountPercentage'];
                $products[$pIndex]['inStock'] = $childData['inStock'];
                $products[$pIndex]['stockQty'] = $highestChildQty;
            }

            if (isset($productAttributesData[$id])) {
                $attributesForProduct = $productAttributesData[$id];

                foreach ($attributesForProduct as $attributeCode => $attributeData) {
                    $attributeName = $attributeData['name'];
                    $attributeCode = $attributeData['code'];
                    $attributeValue = $attributeData['value'];

                    $products[$pIndex]['attributes'][] = [
                        'id' => $attributeCode,
                        'name' => $attributeName,
                        'values' => [
                            [
                                'value' => [$attributeValue],
                                'inStock' => $product['inStock'],
                                'variationId' => $product['id']
                            ]
                        ],
                        'isSearchable' => false,
                        'isFilterable' => true,
                        'addInAutocomplete' => false,
                        'autocompletePosition' => false,
                        'autocompleteGlue' => false
                    ];
                }
            }
        }

        // Setting product stock value in Color atribute data
        foreach ($products as $pIndex => $product) {
            if(isset($products[$pIndex]['colors'])) {
                foreach($products[$pIndex]['colors'] as $pColorIndex => $productColor) {
                    if($products[$pIndex]['id'] == $products[$pIndex]['colors'][$pColorIndex]['variationId']) {
                        $products[$pIndex]['colors'][$pColorIndex]['inStock'] = $products[$pIndex]['inStock'];
                    }
                }
            }
        }
        
        $parentProductReviewData = [];
        foreach ($products as $product) {
            $isParentProduct = $this->isParentProduct($product);
            if ($isParentProduct) {
                $parentProductAttributes[$product['id']] = $this->getParentProductAttributes($product);
                $parentProductReviewData[$product['id']] = [
                    'totalReviews' => $product['totalReviews'] ?? 0,
                    'avgRatings' => $product['avgRatings'] ?? 0,
                ];
            }
        }

        foreach($products as $pIndex => $product) {
            if (isset($product['groupId']) && $product['groupId']) {
                $parentPId = $product['groupId'];
                
                $childProductAttributes = $products[$pIndex]['attributes'];
                $attributeToBeReplaced =  [
                    "bestseller" => 0,
                    "newarrivals" => 0,
                    "clearance_sale" => 0,
                    "can_print" => 0,
                    "waterproof" => 0,
                    "waterrepel" => 0,
                    "limitedstock" => 0,
                    "playerschoice" => 0
                ];

                foreach ($childProductAttributes as $cIndex => $childProductAttribute) {
                    if (isset($parentProductAttributes[$parentPId])) {
                        $parentAttributesValues = $parentProductAttributes[$parentPId];
                        if (isset($attributeToBeReplaced[$childProductAttribute['id']])) {
                            if (isset($parentAttributesValues[$childProductAttribute['id']])) {
                                if (!empty($parentAttributesValues[$childProductAttribute['id']]['values'])) {
                                    $childProductAttributes[$cIndex]['values'][0]['value'][0] = $parentAttributesValues[$childProductAttribute['id']]['values'][0]['value'][0];
                                }
                            }
                        }
                    }
                }
                $products[$pIndex]['attributes'] = $childProductAttributes;

                if (isset($parentProductReviewData[$parentPId])) {
                    $products[$pIndex]['totalReviews'] = $parentProductReviewData[$parentPId]['totalReviews'];
                    $products[$pIndex]['avgRatings'] = $parentProductReviewData[$parentPId]['avgRatings'];
                }
            }
        }
        
        $data->setData('products', $products);
        return $data;
    }

    public function getBundleProducts(array $productIds)
    {
        $bundleProducts = [];

        $productCollection = $this->productFactory->create()->getCollection()
            ->addIdFilter($productIds);

        foreach ($productCollection as $product) {
            if ($product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
                $bundleProducts[] = $product;
            }
        }

        return $bundleProducts;
    }

    public function getProductStockDetails($product)
    {
        $data = [];
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        $data = [
            'inStock' => $stockItem->getIsInStock(),
            'qty' => $stockItem->getQty() > 0 ? $stockItem->getQty() : 0,
        ];
        return $data;
    }

    public function attributesToBeAdded($productIds)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();

        $m2Products = $this->productRepository->getList($searchCriteria)->getItems();

        $productAttributesData = [];
        $requiredAttributes = [
            "bestseller" => 0,
            "newarrivals" => 0,
            "clearance_sale" => 0,
            "can_print" => 0,
            "waterproof" => 0,
            "waterrepel" => 0,
            "limitedstock" => 0,
            "playerschoice" => 0
        ];

        foreach ($m2Products as $m2Product) {
            $m2ProductAttributes = $m2Product->getAttributes();

            $attributeData = [];

            foreach ($m2ProductAttributes as $m2ProductAttribute) {
                $attributeCode = $m2ProductAttribute->getAttributeCode();
                if (isset($requiredAttributes[$attributeCode])) {
                    $attributeData[$m2ProductAttribute->getAttributeCode()] = [
                        'name' => $m2ProductAttribute->getDefaultFrontendLabel(),
                        'code' => $m2ProductAttribute->getAttributeCode(),
                        'value' => $m2Product->getData($attributeCode)
                    ];
                }
            }

            if (!empty($attributeData)) {
                $productAttributesData[$m2Product->getId()] = $attributeData;
            }
        }

        return $productAttributesData;
    }

    public function getChildProducts($bundleProduct)
    {
        $childProducts = [];

        if ($bundleProduct->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            $selections = $bundleProduct->getTypeInstance()->getSelectionsCollection(
                $bundleProduct->getTypeInstance()->getOptionsIds($bundleProduct),
                $bundleProduct
            );
            foreach ($selections as $selection) {
                $childProducts[] = $this->productFactory->create()->load($selection->getProductId());
            }
        }

        return $childProducts;
    }
    private function isParentProduct($product)
    {
        return empty($product['groupId']) && isset($product['childData']);
    }
    public function getParentProductAttributes($product)
    {
        $newAttributeArray = [];

        foreach ($product['attributes'] as $index => $parentProductAttributes) {
            $valuesOfParent = [];
            foreach ($parentProductAttributes['values'] as $parentProductAttributeValues) {
                if ($parentProductAttributeValues['variationId'] == $product['id']) {
                    $valuesOfParent[] = $parentProductAttributeValues;
                }
            }
            $product['attributes'][$index]['values'] = $valuesOfParent;
            $newAttributeArray[$parentProductAttributes['id']] = $product['attributes'][$index];
        }
        return $newAttributeArray;
    }
}
