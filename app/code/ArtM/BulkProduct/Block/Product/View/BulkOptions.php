<?php
/**
 * Copyright © ArtM. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace ArtM\BulkProduct\Block\Product\View;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\Json\EncoderInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class BulkOptions extends Template
{
    protected $registry;
    protected $jsonEncoder;
    protected $getSalableQuantityDataBySku;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        EncoderInterface $jsonEncoder,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku = null,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->jsonEncoder = $jsonEncoder;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        parent::__construct($context, $data);
    }

    /**
     * Get current product
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Check if bulk ordering is enabled for current product
     */
    public function isBulkOrderingEnabled()
    {
        $product = $this->getProduct();
        // For now, enable for all configurable products to test
        // return $product && $product->getTypeId() == Configurable::TYPE_CODE;
        return $product && $product->getTypeId() == Configurable::TYPE_CODE && $product->getData('enable_bulk_ordering'); //Later use: 
    }

    /**
     * Get size options from configurable product
     */
    public function getSizeOptions()
    {
        $product = $this->getProduct();
        $options = [];

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $configurableProduct = $product->getTypeInstance();
            $childProducts = $configurableProduct->getUsedProducts($product);
            
            foreach ($childProducts as $child) {
                $size = $this->getSizeAttributeValue($child);
                if ($size) {
                    $stockQty = $this->getStockQty($child);
                    $isSaleable = $this->isProductSaleable($child);
                    
                    $options[] = [
                        'value' => $child->getId(),
                        'label' => $size,
                        'size' => $size,
                        'product_id' => $child->getId(),
                        'sku' => $child->getSku(),
                        'price' => $child->getFinalPrice(),
                        'stock_qty' => $stockQty,
                        'is_saleable' => $isSaleable,
                        'debug_info' => "SKU: {$child->getSku()}, Stock: {$stockQty}, Saleable: " . ($isSaleable ? 'Yes' : 'No') . ", ID: {$child->getId()}"
                    ];
                }
            }
        }

        return $options;
    }

    /**
     * Get size attribute value from product
     */
    protected function getSizeAttributeValue($product)
    {
        // Get configurable attributes from parent
        $parentProduct = $this->getProduct();
        $configurableProduct = $parentProduct->getTypeInstance();
        $configurableAttributes = $configurableProduct->getConfigurableAttributes($parentProduct);
        
        foreach ($configurableAttributes as $configurableAttribute) {
            $attributeCode = $configurableAttribute->getProductAttribute()->getAttributeCode();
            $attributeValue = $product->getData($attributeCode);
            
            if ($attributeValue) {
                $attribute = $configurableAttribute->getProductAttribute();
                if ($attribute->usesSource()) {
                    return $attribute->getSource()->getOptionText($attributeValue);
                }
                return $attributeValue;
            }
        }
        
        return $product->getName(); // Fallback to product name
    }

    /**
     * Get stock quantity for product (MSI compatible)
     */
    protected function getStockQty($product)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        try {
            // Method 1: Try MSI GetProductSalableQtyInterface
            if (interface_exists('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface')) {
                $getSalableQuantity = $objectManager->get('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');
                $stockResolver = $objectManager->get('Magento\InventorySalesApi\Api\StockResolverInterface');
                $salesChannelFactory = $objectManager->get('Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory');
                
                $websiteCode = $this->_storeManager->getWebsite()->getCode();
                $salesChannel = $salesChannelFactory->create([
                    'data' => [
                        'type' => 'website',
                        'code' => $websiteCode
                    ]
                ]);
                $stock = $stockResolver->execute('website', $websiteCode);
                
                $qty = $getSalableQuantity->execute($product->getSku(), $stock->getStockId());
                return max(0, $qty); // Ensure non-negative
            }
        } catch (\Exception $e) {
            // Continue to next method
        }

        try {
            // Method 2: Try direct MSI source item check
            if (interface_exists('Magento\InventoryApi\Api\SourceItemRepositoryInterface')) {
                $sourceItemRepository = $objectManager->get('Magento\InventoryApi\Api\SourceItemRepositoryInterface');
                $searchCriteriaBuilder = $objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
                
                $searchCriteria = $searchCriteriaBuilder
                    ->addFilter('sku', $product->getSku())
                    ->create();
                
                $sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();
                $totalQty = 0;
                
                foreach ($sourceItems as $sourceItem) {
                    if ($sourceItem->getStatus() == 1) { // In stock
                        $totalQty += $sourceItem->getQuantity();
                    }
                }
                
                if ($totalQty > 0) {
                    return $totalQty;
                }
            }
        } catch (\Exception $e) {
            // Continue to fallback
        }

        try {
            // Method 3: Legacy inventory fallback
            $stockRegistry = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface');
            $stockItem = $stockRegistry->getStockItem($product->getId());
            return $stockItem->getQty();
        } catch (\Exception $e) {
            // Final fallback
            return 0;
        }
    }

    /**
     * Check if product is saleable (simplified and reliable)
     */
    protected function isProductSaleable($product)
    {
        try {
            // Basic saleable check
            if (!$product->isSaleable()) {
                return false;
            }
            
            // Simple approach: check if stock quantity > 0
            $stockQty = $this->getStockQty($product);
            return $stockQty > 0;
            
        } catch (\Exception $e) {
            // Final fallback - check basic product saleable status
            return $product->isSaleable();
        }
    }

    /**
     * Get predefined quantity options
     */
    public function getQuantityOptions()
    {
        $product = $this->getProduct();
        $options = $product->getData('bulk_quantity_options');
        
        if ($options) {
            $quantities = explode(',', $options);
            return array_map('trim', $quantities);
        }
        
        return ['5', '10', '15', '20']; // Default options
    }

    /**
     * Get JSON configuration for JavaScript
     */
    public function getJsonConfig()
    {
        $config = [
            'sizeOptions' => $this->getSizeOptions(),
            'quantityOptions' => $this->getQuantityOptions(),
            'addToCartUrl' => $this->getUrl('bulkproduct/cart/add'),
            'productId' => $this->getProduct()->getId()
        ];

        return $this->jsonEncoder->encode($config);
    }

    /**
     * Check if product has size variations
     */
    public function hasSizeVariations()
    {
        return !empty($this->getSizeOptions());
    }
}