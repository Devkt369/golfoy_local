<?php
/**
 * Debug Controller to get product information
 */

namespace ArtM\SeparateBundleCart\Controller\Debug;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class ProductInfo extends Action implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            $productId = $this->getRequest()->getParam('product_id');
            
            if (!$productId) {
                return $result->setData(['error' => 'No product ID provided']);
            }
            
            $product = $this->productRepository->getById($productId);
            
            $data = [
                'product_id' => $product->getId(),
                'product_type' => $product->getTypeId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'add_product_separate' => $product->getData('add_product_separate'),
                'selected_products' => []
            ];
            
            // If it's a bundle product, get the simple products
            if ($product->getTypeId() == 'bundle') {
                $typeInstance = $product->getTypeInstance();
                $selectionsCollection = $typeInstance->getSelectionsCollection(
                    $typeInstance->getOptionsIds($product),
                    $product
                );
                
                foreach ($selectionsCollection as $selection) {
                    try {
                        $simpleProduct = $this->productRepository->getById($selection->getProductId());
                        $data['selected_products'][] = [
                            'id' => $simpleProduct->getId(),
                            'sku' => $simpleProduct->getSku(),
                            'name' => $simpleProduct->getName(),
                            'selection_id' => $selection->getSelectionId(),
                            'option_id' => $selection->getOptionId()
                        ];
                    } catch (\Exception $e) {
                        // Skip if product not found
                        continue;
                    }
                }
            }
            
            return $result->setData($data);
            
        } catch (\Exception $e) {
            return $result->setData(['error' => $e->getMessage()]);
        }
    }
}