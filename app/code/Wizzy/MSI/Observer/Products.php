<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Wizzy\MSI\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Wizzy\MSI\Services\Catalogue\ProductInventoryManager;
use Wizzy\Search\Services\Store\StoreCatalogueConfig;

class Products implements ObserverInterface
{
    private $storeCatalogueConfig;
    private $productInventoryManager;
    private $productRepository;

    public function __construct(
        ProductInventoryManager    $productInventoryManager,
        ProductRepositoryInterface $productRepository,
        StoreCatalogueConfig       $storeCatalogueConfig

    ) {
        $this->productInventoryManager = $productInventoryManager;
        $this->productRepository = $productRepository;
        $this->storeCatalogueConfig = $storeCatalogueConfig;
    }

    public function execute(Observer $observer)
    {
        $data = $observer->getData('data');
        $products = $data->getDataByKey('products');
        // $isUsingInventoryManagement = $this->storeCatalogueConfig->isUsingInventoryManagement();
        // foreach ($products as $index => $product) {
        //     $sku = $this->productInventoryManager->getProductSKU($product);
        //     if ($this->isParentProduct($product)) {
        //         $parentInStock = false;
        //         $parentStockQty = 0;
        //         $parentProduct = $this->productRepository->get($sku);
        //         $children = $parentProduct->getTypeInstance()->getUsedProducts($parentProduct);
        //         foreach ($children as $child) {
        //             $childSku = $child->getData('sku');
        //             $stockData = $this->productInventoryManager->getData($childSku);
        //             if (isset($stockData)) {
        //                 if ($parentStockQty == 0) {
        //                     $parentStockQty = $stockData['qty'];
        //                 }
        //                 if ($stockData['qty'] > $parentStockQty) {
        //                     $parentStockQty = $stockData['qty'];
        //                 }
        //                 if ($stockData['inStock'] && $stockData['qty'] > 0) {
        //                     $parentInStock = true;
        //                 }
        //             }
        //         }
        //         $stockData = [
        //             'inStock' => $parentInStock,
        //             'qty' => ($parentStockQty < 0) ? 0 : $parentStockQty,
        //         ];
        //     } else {
        //         $stockData = $this->productInventoryManager->getData($sku);
        //     }
        //     if ($stockData !== null) {
        //         $products[$index]['inStock'] = ($stockData['inStock'] && $stockData['qty'] > 0) ? true : false;
        //         $products[$index]['stockQty'] = ($stockData['qty'] < 0) ? 0 : $stockData['qty'];
        //     }
        // }
        
        // $data->setData('products', $products);
       
        return $data;
    }

    private function isParentProduct($product)
    {
        return empty($product['groupId']) && isset($product['childData']);
    }
}
