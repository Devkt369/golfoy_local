<?php

namespace ArtM\MsiPriority\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;

class PayloadBuilder
{
    /**
     * Temporary hardcoded mapping
     * Magento Attribute Set => Vinculum lasthieracyCode
     */
    private array $hierarchyMap = [

        // Core Accessories
        'Grip' => 'Grip',
        'Tee' => 'Tee',
        'Gloves' => 'Gloves',
        'Head Cover' => 'Head Cover',
        'Bags' => 'Bags',
        'Clips & Markers' => 'Clips & Markers',
        'Brushes/Tools' => 'Brushes/Tools',
        'Mats' => 'Mats',
        'Arm Sleeves' => 'Arm Sleeves',
        'Shoe Spike' => 'Shoe Spike',
        'Towels' => 'Towels',
        'Swag' => 'Swag',
        'Practice' => 'Practice',
        'Umbrella' => 'Umbrella',

        // Footwear & Apparel
        'Shoes' => 'Shoes',
        'Trolley' => 'Trolley',
        'T-Shirt' => 'T-Shirt',
        'Jackets' => 'Jackets',
        'Belts' => 'Belts',
        'Headwear' => 'Headwear',
        'Watches' => 'Watches',
        'Eyewear' => 'Eyewear',
        'Footwear Care' => 'Footwear Care',
        'Wrist Band' => 'Wrist Band',
        'Sweater' => 'Sweater',
        'Vests' => 'Vests',
        'Shorts' => 'Shorts',
        'Trousers' => 'Trousers',
        'Socks' => 'Socks',
        'Shoes Insoles' => 'Shoes Insoles',

        // Clubs
        'Drivers' => 'Drivers',
        'Fairways' => 'Fairways',
        'Hybrid' => 'Hybrid',
        'Irons' => 'Irons',
        'Wedges' => 'Wedges',
        'Putter' => 'Putter',
        'Utility Iron' => 'Utility Iron',
        'Individual Irons' => 'Individual Irons',
        'Full Sets' => 'Full Sets',

        // Golf Equipment
        'Rangefinder' => 'Rangefinder',
        'Ball' => 'Ball',
        'Ball Sleeve' => 'Ball Sleeve',
        'Shaft' => 'Shaft',
        'Ferrule' => 'Ferrule',
        'Shaft Extensions' => 'Shaft Extensions',

        // Junior
        'Junior Full Set' => 'Junior Full Set',
        'Junior Individual Club' => 'Junior Individual Club',
        'Junior Bags' => 'Junior Bags',
        'Junior Bag' => 'Junior Bags',

        // Used Products
        'Used Putter' => 'Used Putter',
        'Used Fairway' => 'Used Fairways',
        'Used Driver' => 'Used Drivers',
        'Used Wedges' => 'Used Wedges',
        'Used Iron' => 'Used Irons',
        'Used Hybrid' => 'Used Hybrid',
        'Used Junior Full Set' => 'Used Junior Full Set',

        // Special Categories
        'Ogio Bags' => 'Ogio Bags',
        'Reservoir' => 'Reservoir',
        'Tourism Package' => 'Tourism Package',
        'Simulator/Launch Monitor' => 'Simulator/Launch Monitor',
        'Golf Supporters' => 'Golf Supporters',
        'Golf Gifts' => 'Golf Gifts',
        'Services' => 'Services',
        'Game' => 'Game',
        'Demo Club' => 'Demo Club',
        'Demo Products' => 'Demo Products',
        'Massagers' => 'Massagers',
        'Sipper' => 'Sipper',
        'Sunscreen' => 'Sunscreen',
        'Neck Warmer' => 'Neck Warmer',
        'Headband' => 'Headband',

        // Women Category
        'Women T-Shirt' => 'Women T-Shirt',
        'Women Skort' => 'Women Skort',
        'Women Skirt' => 'Women Skirt',

        // Default
        'Default' => 'Default',
    ];

    private AttributeSetRepositoryInterface $attributeSetRepository;

    public function __construct(
        AttributeSetRepositoryInterface $attributeSetRepository
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
    }

    public function buildSkuPayload(ProductInterface $product)
    {
        $attributeSetId = $product->getAttributeSetId();

        $attributeSet = $this->attributeSetRepository->get($attributeSetId);
        $attributeSetName = trim($attributeSet->getAttributeSetName());

        // Find mapping
        $lastHierarchyCode =
            $this->hierarchyMap[$attributeSetName]
            ?? $this->hierarchyMap['Default'];
        return [
            "skuList" => [
                [
                    "skuCode" => (string)$product->getSku(),
                    "skuName" => (string)$product->getName(),
                    "classification" => "Normal",
                    "mrp" => (float)$product->getPrice(),
                    "salePrice" => (float)$product->getFinalPrice(),
                    "baseCost" => (float)($product->getCost() ?: $product->getPrice()),
                    "taxCategory" => $product->getAttributeText('hsn_code') ?? "66019900",
                    "isActive" => $product->isDisabled() ? "no" : "yes",
                    "vendorCode" => "D01",
                    "isSaleable" => $product->isSalable() ? "yes" : "no",
                    "lasthieracyCode" => $lastHierarchyCode
                ]
            ]
        ];
    }

    public function buildChannelMapPayload(ProductInterface $product, array $sourceItemContext, Config $config, $storeId = null)
    {
        $currentSku = (string) $product->getSku();

        return [
            "skuList" => [
                [
                    "locCode" => "M01",
                    "skuCode" => $currentSku,
                    "channelSkuCode" => $currentSku,
                    "channelProductId" => (string) $product->getId(),
                    "channelPrice" => (string) $product->getFinalPrice(),
                    "status" => "1",
                    "pickInstruction" => "",
                    "bufferStock" => "",
                    "backOrderQty" => ""
                ]
            ]
        ];
    }
}
