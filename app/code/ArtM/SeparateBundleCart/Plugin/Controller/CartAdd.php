<?php
/**
 * ArtM SeparateBundleCart Plugin for Cart Add Controller
 *
 * @author ArtM
 * @package ArtM_SeparateBundleCart
 */

namespace ArtM\SeparateBundleCart\Plugin\Controller;

use Magento\Checkout\Controller\Cart\Add as CartAddController;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Psr\Log\LoggerInterface;

class CartAdd
{
    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

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
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        ProductRepositoryInterface $productRepository,
        Cart $cart,
        LoggerInterface $logger
    ) {
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->productRepository = $productRepository;
        $this->cart = $cart;
        $this->logger = $logger;
    }

    /**
     * Around plugin for execute method
     */
    public function aroundExecute(CartAddController $subject, callable $proceed)
    {
        try {
            $request = $subject->getRequest();
            $debugFile = BP . '/var/log/artm_controller_' . date('Y-m-d') . '.log';
            
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Controller plugin called\n", FILE_APPEND);
            
            $productId = (int) $request->getParam('product');
            if (!$productId) {
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - No product ID found\n", FILE_APPEND);
                return $proceed();
            }

            $product = $this->productRepository->getById($productId);
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Product: " . $product->getId() . " (" . $product->getTypeId() . ")\n", FILE_APPEND);

            // Check if it's a bundle product
            if ($product->getTypeId() !== 'bundle') {
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Not a bundle product\n", FILE_APPEND);
                return $proceed();
            }

            // Check if the product has the add_product_separate attribute set to "Yes"
            $addProductSeparate = $product->getData('add_product_separate');
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - add_product_separate: " . ($addProductSeparate ?: 'null') . "\n", FILE_APPEND);

            if ($addProductSeparate != 1 && $addProductSeparate !== 'Yes') {
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Attribute not set to Yes, proceeding normally\n", FILE_APPEND);
                return $proceed();
            }

            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Processing bundle separately\n", FILE_APPEND);

            // Get bundle options
            $bundleOption = $request->getParam('bundle_option', []);
            $bundleOptionQty = $request->getParam('bundle_option_qty', []);

            if (empty($bundleOption)) {
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - No bundle options found\n", FILE_APPEND);
                $this->messageManager->addErrorMessage(__('Please select all required options.'));
                return $this->redirectFactory->create()->setPath('*/*/');
            }

            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Bundle options: " . json_encode($bundleOption) . "\n", FILE_APPEND);

            // Load bundle selections
            $typeInstance = $product->getTypeInstance();
            $selectionsCollection = $typeInstance->getSelectionsCollection(
                $typeInstance->getOptionsIds($product),
                $product
            );

            $addedCount = 0;
            $addedProducts = [];

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
                            $addedCount++;
                            $addedProducts[] = $simpleProduct->getName();

                            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Added: " . $simpleProduct->getName() . " (Qty: $qty)\n", FILE_APPEND);
                            break;
                        }
                    }
                }
            }

            if ($addedCount > 0) {
                $this->cart->save();
                $this->messageManager->addSuccessMessage(
                    __('Added %1 individual products to cart: %2', $addedCount, implode(', ', $addedProducts))
                );
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Successfully added $addedCount products\n", FILE_APPEND);
            } else {
                $this->messageManager->addErrorMessage(__('No products were added to cart.'));
                file_put_contents($debugFile, date('Y-m-d H:i:s') . " - No products added\n", FILE_APPEND);
            }

            // Return redirect to cart
            return $this->redirectFactory->create()->setPath('checkout/cart');

        } catch (\Exception $e) {
            $debugFile = BP . '/var/log/artm_controller_' . date('Y-m-d') . '.log';
            file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Controller Error: " . $e->getMessage() . "\n", FILE_APPEND);
            $this->logger->error('ArtM SeparateBundleCart Controller Error: ' . $e->getMessage());
            return $proceed();
        }
    }
}