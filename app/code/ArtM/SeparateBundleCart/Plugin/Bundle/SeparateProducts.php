<?php
/**
 * ArtM SeparateBundleCart Plugin for Bundle Product Type
 *
 * @author ArtM
 * @package ArtM_SeparateBundleCart
 */

namespace ArtM\SeparateBundleCart\Plugin\Bundle;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class SeparateProducts
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Around plugin for prepareForCart method
     * Intercepts bundle product cart addition and processes simple products separately
     *
     * @param BundleType $subject
     * @param callable $proceed
     * @param DataObject $buyRequest
     * @param Product $product
     * @param string $processMode
     * @return array|string
     */
    public function aroundPrepareForCart(
        BundleType $subject,
        callable $proceed,
        DataObject $buyRequest,
        Product $product,
        $processMode = null
    ) {
        try {
            // Log the method call
            $this->logger->info('ArtM SeparateBundleCart: prepareForCart called for product: ' . $product->getId());
            
            // Check if the product has the add_product_separate attribute set to "Yes"
            $addProductSeparate = $product->getData('add_product_separate');
            
            $this->logger->info('ArtM SeparateBundleCart: add_product_separate value: ' . ($addProductSeparate ? 'Yes' : 'No'));
            
            if ($addProductSeparate == 1 || $addProductSeparate === 'Yes') {
                $this->logger->info('ArtM SeparateBundleCart: Processing bundle product separately');
                
                // Get bundle options from buy request
                $bundleOption = $buyRequest->getBundleOption();
                $bundleOptionQty = $buyRequest->getBundleOptionQty();
                
                if (!$bundleOption || !is_array($bundleOption)) {
                    $this->logger->info('ArtM SeparateBundleCart: No bundle options found');
                    return __('Please select all required options.');
                }
                
                $this->logger->info('ArtM SeparateBundleCart: Bundle options: ' . json_encode($bundleOption));
                
                // Get bundle selections and prepare individual products
                $selections = [];
                $bundleSelections = $subject->getSelectionsCollection(
                    $subject->getOptionsIds($product), 
                    $product
                );
                
                foreach ($bundleOption as $optionId => $selectionIds) {
                    if (!is_array($selectionIds)) {
                        $selectionIds = [$selectionIds];
                    }
                    
                    foreach ($selectionIds as $selectionId) {
                        // Find the selection product
                        foreach ($bundleSelections as $selection) {
                            if ($selection->getSelectionId() == $selectionId) {
                                $qty = isset($bundleOptionQty[$optionId]) ? $bundleOptionQty[$optionId] : 1;
                                
                                // Create a new buy request for individual product
                                $individualBuyRequest = new DataObject([
                                    'qty' => $qty,
                                    'product' => $selection->getProductId()
                                ]);
                                
                                $selections[] = [
                                    'product' => $selection,
                                    'qty' => $qty,
                                    'buy_request' => $individualBuyRequest
                                ];
                                
                                $this->logger->info('ArtM SeparateBundleCart: Added selection - Product ID: ' . 
                                    $selection->getProductId() . ', Qty: ' . $qty);
                                break;
                            }
                        }
                    }
                }
                
                if (empty($selections)) {
                    $this->logger->info('ArtM SeparateBundleCart: No valid selections found');
                    return __('Please select all required options.');
                }
                
                $this->logger->info('ArtM SeparateBundleCart: Returning ' . count($selections) . ' individual products');
                
                // Return array of individual products instead of bundle
                return $selections;
            }
            
            // If attribute is not set to "Yes", proceed with normal bundle processing
            $this->logger->info('ArtM SeparateBundleCart: Processing as normal bundle product');
            return $proceed($buyRequest, $product, $processMode);
            
        } catch (\Exception $e) {
            $this->logger->error('ArtM SeparateBundleCart Error: ' . $e->getMessage());
            return __('An error occurred while processing the product.');
        }
    }
}