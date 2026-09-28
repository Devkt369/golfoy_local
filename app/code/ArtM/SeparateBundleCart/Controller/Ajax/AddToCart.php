<?php
/**
 * AJAX Controller to handle separate bundle product addition
 */

namespace ArtM\SeparateBundleCart\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Psr\Log\LoggerInterface;

class AddToCart extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ProductRepositoryInterface $productRepository,
        Cart $cart,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->productRepository = $productRepository;
        $this->cart = $cart;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $debugFile = BP . '/var/log/artm_ajax_' . date('Y-m-d') . '.log';
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - AJAX Controller called\n", FILE_APPEND);
            
            $productId = (int) $this->getRequest()->getParam('product_id');
            $bundleOptions = $this->getRequest()->getParam('bundle_options', []);
            $bundleOptionQty = $this->getRequest()->getParam('bundle_option_qty', []);
            $mainQty = (float) $this->getRequest()->getParam('qty', 1); // Get the main quantity
            
            if (!$productId) {
                return $result->setData(['success' => false, 'message' => 'No product ID provided']);
            }
            
            $product = $this->productRepository->getById($productId);
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Processing product: " . $product->getName() . "\n", FILE_APPEND);
            
            // Verify it's a bundle product with the attribute
            if ($product->getTypeId() !== 'bundle') {
                return $result->setData(['success' => false, 'message' => 'Not a bundle product']);
            }
            
            $addProductSeparate = $product->getData('add_product_separate');
            if ($addProductSeparate != 1 && $addProductSeparate !== 'Yes') {
                return $result->setData(['success' => false, 'message' => 'Attribute not set to separate']);
            }
            
            if (empty($bundleOptions)) {
                return $result->setData(['success' => false, 'message' => 'No bundle options provided']);
            }
            
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Bundle options: " . json_encode($bundleOptions) . "\n", FILE_APPEND);
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Main quantity: " . $mainQty . "\n", FILE_APPEND);
            
            // Load bundle selections
            $typeInstance = $product->getTypeInstance();
            $selectionsCollection = $typeInstance->getSelectionsCollection(
                $typeInstance->getOptionsIds($product),
                $product
            );
            
            $addedProducts = [];
            $addedCount = 0; // Number of different products
            $totalQuantity = 0; // Total quantity of all products
            
            foreach ($bundleOptions as $optionId => $selectionIds) {
                if (!is_array($selectionIds)) {
                    $selectionIds = [$selectionIds];
                }
                
                foreach ($selectionIds as $selectionId) {
                    foreach ($selectionsCollection as $selection) {
                        if ($selection->getSelectionId() == $selectionId) {
                            // Get bundle option quantity (default 1 if not set)
                            $bundleQty = isset($bundleOptionQty[$optionId]) ? (float)$bundleOptionQty[$optionId] : 1;
                            
                            // Calculate final quantity: main quantity × bundle option quantity
                            $finalQty = $mainQty * $bundleQty;
                            
                            // Load the simple product
                            $simpleProduct = $this->productRepository->getById($selection->getProductId());
                            
                            // Add simple product to cart with calculated quantity
                            $this->cart->addProduct($simpleProduct, $finalQty);
                            $addedCount++; // Increment product count
                            $totalQuantity += $finalQty; // Add to total quantity
                            $addedProducts[] = $simpleProduct->getName() . " (Qty: $finalQty)";
                            
                            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Added: " . $simpleProduct->getName() . " (Bundle Qty: $bundleQty, Main Qty: $mainQty, Final Qty: $finalQty)\n", FILE_APPEND);
                            break;
                        }
                    }
                }
            }
            
            if ($addedCount > 0) {
                $this->cart->save();
                
                // Create a more descriptive message
                $message = "Added $totalQuantity items ($addedCount different products):\n" . implode("\n", $addedProducts);
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Success: $addedCount products added, total quantity: $totalQuantity\n", FILE_APPEND);
                
                return $result->setData([
                    'success' => true, 
                    'message' => $message,
                    'count' => $totalQuantity, // Use total quantity instead of product count
                    'product_count' => $addedCount, // Also include product count
                    'products' => $addedProducts
                ]);
            } else {
                return $result->setData(['success' => false, 'message' => 'No products were added']);
            }
            
        } catch (\Exception $e) {
            $debugFile = BP . '/var/log/artm_ajax_' . date('Y-m-d') . '.log';
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - AJAX Error: " . $e->getMessage() . "\n", FILE_APPEND);
            
            return $result->setData(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}