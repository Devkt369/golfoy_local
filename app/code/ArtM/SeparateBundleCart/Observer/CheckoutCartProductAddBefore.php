<?php
/**
 * ArtM SeparateBundleCart Observer for Cart Product Add
 *
 * @author ArtM
 * @package ArtM_SeparateBundleCart
 */

namespace ArtM\SeparateBundleCart\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Psr\Log\LoggerInterface;

class CheckoutCartProductAddBefore implements ObserverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * Constructor
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        Cart $cart
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->cart = $cart;
    }

    /**
     * Execute observer
     */
    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            $request = $observer->getEvent()->getRequest();
            
            // Create debug file
            $debugFile = BP . '/var/log/artm_observer_' . date('Y-m-d') . '.log';
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Observer called for product: " . ($product ? $product->getId() : 'null') . "\n", FILE_APPEND);
            
            // Check if we have valid product and request
            if (!$product || !$request) {
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Missing product or request object\n", FILE_APPEND);
                return;
            }
            
            // Check if it's a bundle product
            if ($product->getTypeId() !== 'bundle') {
                return;
            }
            
            // Check if the product has the add_product_separate attribute set to "Yes"
            $addProductSeparate = $product->getData('add_product_separate');
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Bundle product, add_product_separate: " . ($addProductSeparate ? 'Yes' : 'No') . "\n", FILE_APPEND);
            
            if ($addProductSeparate != 1 && $addProductSeparate !== 'Yes') {
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Attribute not set to Yes, proceeding normally\n", FILE_APPEND);
                return;
            }
            
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Should process separately - but observer approach may be too late\n", FILE_APPEND);
            
            // Note: Observer approach might be too late in the process
            // We'll just log for now and use a different approach
            
        } catch (\Exception $e) {
            $debugFile = BP . '/var/log/artm_observer_' . date('Y-m-d') . '.log';
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Observer Error: " . $e->getMessage() . "\n", FILE_APPEND);
            $this->logger->error('ArtM SeparateBundleCart Observer Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Add individual products to cart
     */
    private function addIndividualProducts($product, $request, $debugFile)
    {
        $bundleOption = $request->getParam('bundle_option');
        $bundleOptionQty = $request->getParam('bundle_option_qty');
        
        if (!$bundleOption || !is_array($bundleOption)) {
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - No bundle options found\n", FILE_APPEND);
            return;
        }
        
        // Load bundle selections
        $typeInstance = $product->getTypeInstance();
        $selectionsCollection = $typeInstance->getSelectionsCollection(
            $typeInstance->getOptionsIds($product),
            $product
        );
        
        foreach ($bundleOption as $optionId => $selectionIds) {
            if (!is_array($selectionIds)) {
                $selectionIds = [$selectionIds];
            }
            
            foreach ($selectionIds as $selectionId) {
                foreach ($selectionsCollection as $selection) {
                    if ($selection->getSelectionId() == $selectionId) {
                        $qty = isset($bundleOptionQty[$optionId]) ? (float)$bundleOptionQty[$optionId] : 1;
                        
                        // Load the simple product
                        $simpleProduct = $this->productRepository->getById($selection->getProductId());
                        
                        // Add simple product to cart
                        $this->cart->addProduct($simpleProduct, $qty);
                        
                        file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Added: " . $simpleProduct->getName() . " (Qty: $qty)\n", FILE_APPEND);
                        break;
                    }
                }
            }
        }
        
        $this->cart->save();
    }
}