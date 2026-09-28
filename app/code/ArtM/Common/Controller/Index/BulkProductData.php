<?php
namespace ArtM\Common\Controller\Index;

use ArtM\Common\Block\Productpriceblock;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use ArtM\EstimateDelivery\Helper\Data as EstimateDeliveryHelper;
use ArtM\EstimateDelivery\Model\DeliveryCacheFactory;
use ArtM\EstimateDelivery\Model\ResourceModel\DeliveryCache as DeliveryCacheResource;

class BulkProductData extends Action implements CsrfAwareActionInterface
{
    /** @var JsonFactory */
    protected $resultJsonFactory;
    /** @var ScopeConfigInterface */
    protected $scopeConfig;
    /** @var GetSourceItemsBySkuInterface */
    protected $getSourceItemsBySku;
    /** @var EstimateDeliveryHelper */
    protected $estimateDeliveryHelper;
    /** @var DeliveryCacheFactory */
    protected $deliveryCacheFactory;
    /** @var DeliveryCacheResource */
    protected $deliveryCacheResource;

    protected $productPriceBlock;
    protected $productCollectionFactory;
    protected $productRepository;
    protected $attributeSetRepository;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductCollectionFactory $productCollectionFactory,
        Productpriceblock $productPriceBlock,
        ProductRepositoryInterface $productRepository,
        AttributeSetRepositoryInterface $attributeSetRepository,
        EstimateDeliveryHelper $estimateDeliveryHelper,
        DeliveryCacheFactory $deliveryCacheFactory,
        DeliveryCacheResource $deliveryCacheResource
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productPriceBlock = $productPriceBlock;
        $this->productRepository = $productRepository;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->estimateDeliveryHelper = $estimateDeliveryHelper;
        $this->deliveryCacheFactory = $deliveryCacheFactory;
        $this->deliveryCacheResource = $deliveryCacheResource;
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $resultJson = $this->resultJsonFactory->create();

        if ($request->getMethod() !== 'POST') {
            return $resultJson->setData(['error' => 'Invalid request method']);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $resultJson->setData(['error' => 'Invalid JSON payload']);
        }

        $skus = isset($data['skus']) ? $data['skus'] : [];
        $productIds = isset($data['product_ids']) ? $data['product_ids'] : [];
        $getFeaturedAttrs = isset($data['get_featured_attrs']) ? (bool)$data['get_featured_attrs'] : false;
        $featuredAttrsConfig = isset($data['featured_attrs_config']) ? $data['featured_attrs_config'] : '';
        $checkEstimationDelivery = isset($data['check_estimation_delivery']) ? (bool)$data['check_estimation_delivery'] : false;
        $useEstimationModuleLogic = isset($data['use_estimation_module_logic']) ? (bool)$data['use_estimation_module_logic'] : false;

        $customOptions = [];
        $expressDelivery = [];
        $sizes = [];
        $featuredAttrs = [];

        // Batch load all products by IDs and SKUs in single optimized collection
        $allProductIds = array_unique($productIds);
        $skuToId = [];
        $allProducts = [];
        $attributeSetCache = [];
        
        // Parse featured attributes config
        $featuredAttrsMapping = [];
        if ($getFeaturedAttrs && $featuredAttrsConfig) {
            $pairs = explode('|', $featuredAttrsConfig);
            foreach ($pairs as $pair) {
                $parts = explode(':', $pair);
                if (count($parts) === 2) {
                    $featuredAttrsMapping[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
        
        if (!empty($skus) || !empty($productIds)) {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect(['can_print', 'sku', 'attribute_set_id']);
            
            // Add featured attributes to selection if needed
            if ($getFeaturedAttrs && !empty($featuredAttrsMapping)) {
                foreach ($featuredAttrsMapping as $attrSetName => $attrCode) {
                    $productCollection->addAttributeToSelect($attrCode);
                }
            }
            
            // Filter by both SKUs and IDs
            if (!empty($skus) && !empty($productIds)) {
                $productCollection->addAttributeToFilter(
                    [
                        ['attribute' => 'sku', 'in' => $skus],
                        ['attribute' => 'entity_id', 'in' => $productIds]
                    ]
                );
            } elseif (!empty($skus)) {
                $productCollection->addFieldToFilter('sku', ['in' => $skus]);
            } else {
                $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
            }
            
            foreach ($productCollection as $product) {
                $productId = $product->getId();
                $allProducts[$productId] = $product;
                $skuToId[$product->getSku()] = $productId;
                if (!in_array($productId, $allProductIds)) {
                    $allProductIds[] = $productId;
                }
                
                // Cache attribute set info to avoid repeated API calls
                if ($getFeaturedAttrs && !empty($featuredAttrsMapping)) {
                    try {
                        if (!isset($attributeSetCache[$product->getAttributeSetId()])) {
                            $attributeSet = $this->attributeSetRepository->get($product->getAttributeSetId());
                            $attributeSetCache[$product->getAttributeSetId()] = strtolower($attributeSet->getAttributeSetName());
                        }
                    } catch (\Exception $e) {
                        $attributeSetCache[$product->getAttributeSetId()] = '';
                    }
                }
            }
        }
        
        // Process customOptions, sizes and featured attributes in optimized loop
        foreach ($allProductIds as $productId) {
            $hasOptions = false;
            $allFree = true;
            $totalOptions = 0;
            $sizesValue = '';
            
            if (isset($allProducts[$productId])) {
                $product = $allProducts[$productId];
                $canPrint = $product->getData('can_print');
                
                // Custom options - reload only if needed for options
                if ($canPrint) {
                    try {
                        $fullProduct = $this->productRepository->getById($productId);
                        $options = $fullProduct->getOptions();
                        if (is_array($options) && count($options) > 0) {
                            $hasOptions = true;
                            $totalOptions = count($options);
                            foreach ($options as $option) {
                                $price = (float)$option->getPrice();
                                if ($price > 0) {
                                    $allFree = false;
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $hasOptions = false;
                        $allFree = false;
                        $totalOptions = 0;
                    }
                }
                
                // Sizes
                $sizeData = $this->productPriceBlock->getSizerollfromDb($productId);
                $sizesValue = isset($sizeData['available_size_roll_value']) ? $sizeData['available_size_roll_value'] : '';
                
                // Featured attributes - use cached attribute set data
                if ($getFeaturedAttrs && !empty($featuredAttrsMapping) && isset($attributeSetCache[$product->getAttributeSetId()])) {
                    $attributeSetName = $attributeSetCache[$product->getAttributeSetId()];
                    
                    // Check if attribute set matches any in mapping
                    foreach ($featuredAttrsMapping as $mappingKey => $attrCode) {
                        $mappingKeyLower = strtolower(trim($mappingKey));
                        
                        if (strpos($attributeSetName, $mappingKeyLower) !== false || strpos($mappingKeyLower, $attributeSetName) !== false) {
                            // Load full product for attribute values if not already loaded
                            if (!isset($fullProduct)) {
                                try {
                                    $fullProduct = $this->productRepository->getById($productId);
                                } catch (\Exception $e) {
                                    $fullProduct = $product;
                                }
                            }
                            
                            $attrValue = $fullProduct->getAttributeText($attrCode);
                            if (!$attrValue) {
                                $attrValue = $fullProduct->getData($attrCode);
                            }
                            
                            if ($attrValue && $attrValue !== '0' && $attrValue !== 0 && !empty($attrValue)) {
                                // Get attribute label
                                $attr = $product->getResource()->getAttribute($attrCode);
                                $attrLabel = $attr ? $attr->getStoreLabel() : ucfirst(str_replace('_', ' ', $attrCode));
                                
                                $featuredAttrs[$productId] = [
                                    'attrName' => $attrCode,
                                    'attrLabel' => $attrLabel,
                                    'attrValue' => $attrValue,
                                    'attributeSetName' => $attributeSetName
                                ];
                                break; // Found a match, no need to check others
                            }
                        }
                    }
                }
            }
            
            $customOptions[$productId] = [
                'hasOptions' => $hasOptions,
                'allFree' => $allFree,
                'totalOptions' => $totalOptions
            ];
            
            // Map sizes to SKU if possible
            foreach ($skuToId as $sku => $id) {
                if ($id == $productId) {
                    $sizes[$sku] = $sizesValue;
                }
            }
        }

        // Express Delivery logic - use estimation delivery table if new parameters are provided
        if ($checkEstimationDelivery && $useEstimationModuleLogic) {
            // Use centralized EstimateDelivery helper for processing
            $expressDelivery = $this->estimateDeliveryHelper->processDeliveryForSkus($skus, $skuToId);
        } else {
            // Original logic: Check inventory sources directly
            $ownSources = $this->scopeConfig->getValue(
                'artm_estimatedelivery/own_source/all_source',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $ownSourcesArr = $ownSources ? array_map('trim', explode(',', $ownSources)) : [];

            if (!empty($skus)) {
                foreach ($skus as $sku) {
                    $isExpress = false;
                    try {
                        $sourceItems = $this->getSourceItemsBySku->execute($sku);
                        foreach ($sourceItems as $item) {
                            if (in_array($item->getSourceCode(), $ownSourcesArr) && $item->getQuantity() > 0) {
                                $isExpress = true;
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        $isExpress = false;
                    }
                    $expressDelivery[$sku] = $isExpress;
                }
            }
        }

        $response = [
            'customOptions' => $customOptions,
            'expressDelivery' => $expressDelivery,
            'sizes' => $sizes,
            'featuredAttrs' => $featuredAttrs
        ];

        return $resultJson->setData($response);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
