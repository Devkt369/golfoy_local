<?php
/**
 * Product Icons ViewModel
 */
declare(strict_types=1);

namespace ArtM\Common\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductData implements ArgumentInterface
{
    private $registry;
    private $product;
    private $iconsData;
    private $stockRegistry;
    private $getProductSalableQty;
    private $stockResolver;
    private $storeManager;

    public function __construct(
        Registry $registry,
        StockRegistryInterface $stockRegistry,
        GetProductSalableQtyInterface $getProductSalableQty,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->registry = $registry;
        $this->stockRegistry = $stockRegistry;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
    }

    /**
     * Get current product
     */
    private function getProduct(): ?Product
    {
        if ($this->product === null) {
            $this->product = $this->registry->registry('current_product');
        }
        return $this->product;
    }

    /**
     * Get brand text
     */
    public function getBrand(): ?string
    {
        $product = $this->getProduct();
        if (!$product) {
            return null;
        }
        $brand = $product->getAttributeText('brand');
        return $brand ? (string)$brand : null;
    }

    /**
     * Get product name without brand
     */
    public function getCleanProductName(): ?string
{
        $product = $this->getProduct();

        if (!$product) {
            return null;
        }

        $productName = trim((string)$product->getName());
        $brand = trim((string)$this->getBrand());

        if (!$brand) {
            return $productName;
        }

        // Create a flexible regex that ignores spaces and hyphens
        // Example: TaylorMade becomes T[\s-]*a[\s-]*y[\s-]*l...
        $brandCharacters = preg_split('//u', $brand, -1, PREG_SPLIT_NO_EMPTY);

        $pattern = '/^';

        foreach ($brandCharacters as $char) {
            if ($char !== ' ' && $char !== '-') {
                $pattern .= preg_quote($char, '/') . '[\s-]*';
            }
        }

        $pattern .= '/i';

        // Remove brand from the start of product name
        $cleanName = preg_replace($pattern, '', $productName);

        // Remove leftover separators like "-", " - ", etc.
        $cleanName = ltrim($cleanName, " \t\n\r\0\x0B-");

        return trim($cleanName);
    }

    /**
     * Get returnable policy
     */
    public function getReturnable(): ?string
    {
        $product = $this->getProduct();
        if (!$product) {
            return null;
        }
        $returnable = $product->getAttributeText('returnable');
        return $returnable ? (string)$returnable : null;
    }

    /**
     * Get replacement policy
     */
    public function getReplacement(): ?string
    {
        $product = $this->getProduct();
        if (!$product) {
            return null;
        }
        $replacement = $product->getAttributeText('replacement_policy');
        return $replacement ? (string)$replacement : null;
    }

    /**
     * Get warranty information
     */
    public function getWarranty(): ?string
    {
        $product = $this->getProduct();
        if (!$product) {
            return null;
        }
        $warranty = $product->getAttributeText('warranty');
        return $warranty ? (string)$warranty : null;
    }

    /**
     * Get all icons data with conditions
     */
    public function getIconsData(): array
    {
        if ($this->iconsData === null) {
            $brand = $this->getBrand();
            $returnable = $this->getReturnable();
            $replacement = $this->getReplacement();
            $warranty = $this->getWarranty();

            $this->iconsData = [
                [
                    'show' => true,
                    'img' => 'original-icon.svg',
                    'text' => '100% Original Product',
                    'tooltip' => 'Directly from authorized distributor for ' . ($brand ?: '')
                ],
                [
                    'show' => $returnable != 'No',
                    'img' => 'easy-return.svg',
                    'text' => '7 Days Return',
                    'tooltip' => 'Return or exchange within 7 days'
                ],
                [
                    'show' => $replacement != 'No',
                    'img' => 'easy-exchange.svg',
                    'text' => 'Easy Replacement',
                    'tooltip' => 'Only if defective or wrong item'
                ],
                [
                    'show' => $warranty != false,
                    'img' => 'warranty-icon.svg',
                    'text' => 'Warranty',
                    'tooltip' => $warranty ?: 'Warranty available'
                ],
                [
                    'show' => true,
                    'img' => 'free-delivery.svg',
                    'text' => 'Free Delivery',
                    'tooltip' => 'No shipping charges'
                ],
                [
                    'show' => true,
                    'img' => 'pay-on-delivery.svg',
                    'text' => 'Pay on Delivery',
                    'tooltip' => 'Cash or Pay Later available'
                ]
            ];
        }

        return $this->iconsData;
    }

    /**
     * Get only icons that should be displayed
     */
    public function getVisibleIcons(): array
    {
        return array_filter($this->getIconsData(), function($icon) {
            return $icon['show'];
        });
    }

    /**
     * Get base media URL for icons
     */
    public function getIconBaseUrl(): string
    {
        return 'https://golfoy.com/media/wysiwyg/product_feature_icon/new/';
    }

    /**
     * Get product final price
     */
    public function getFinalPrice(): ?float
    {
        $product = $this->getProduct();
        if (!$product) {
            return null;
        }

        $regularPrice = $product->getPrice();
        $specialPrice = $product->getFinalPrice();
        
        return (float)$specialPrice ?: (float)$regularPrice;
    }

    /**
     * Check if EMI should be displayed
     */
    public function shouldShowEmi(): bool
    {
        $finalPrice = $this->getFinalPrice();
        return $finalPrice && $finalPrice > 10000;
    }

    /**
     * Get EMI amount
     */
    public function getEmiAmount(): ?int
    {
        if (!$this->shouldShowEmi()) {
            return null;
        }

        $finalPrice = $this->getFinalPrice();
        return $finalPrice ? (int)($finalPrice / 6) : null;
    }

    /**
     * Get formatted EMI amount with commas
     */
    public function getFormattedEmiAmount(): ?string
    {
        $emiAmount = $this->getEmiAmount();
        if (!$emiAmount) {
            return null;
        }

        return preg_replace("/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i", "$1,", (string)$emiAmount);
    }

    /**
     * Get stock quantity for a product using MSI
     */
    public function getProductStockQuantity(int $productId): ?float
    {
        try {
            // Get product to retrieve SKU
            $product = $this->getProduct();
            if (!$product && $productId) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
                $product = $productRepository->getById($productId);
            }
            
            if (!$product) {
                return null;
            }

            // Get website code
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            
            // Get stock using MSI
            $stock = $this->stockResolver->execute('website', $websiteCode);
            $stockId = $stock->getStockId();
            
            // Get saleable quantity using MSI
            $saleableQty = $this->getProductSalableQty->execute($product->getSku(), $stockId);
            
            return (float)$saleableQty;
            
        } catch (\Exception $e) {
            // Fallback to old method if MSI fails
            try {
                $stockItem = $this->stockRegistry->getStockItem($productId);
                if ($stockItem) {
                    return (float)$stockItem->getQty();
                }
                return null;
            } catch (\Exception $fallbackException) {
                return null;
            }
        }
    }
}