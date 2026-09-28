<?php
namespace ArtM\SimilarProducts\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Helper\Image;

class SimilarProducts extends Template
{
    protected $_productRepository;
    protected $_registry;
    protected $_urlBuilder;
    protected $_attributeRepository;
    protected $_productAttributeRepository;
    public $imageFactory;
    const SMALL_IMAGE = 'small_image';

    public function __construct(
        Template\Context $context,
        ProductRepositoryInterface $productRepository,
        Registry $registry,
        UrlInterface $urlBuilder,
        Image $productImageHelper,
        AttributeRepositoryInterface $attributeRepository,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ImageFactory $imageFactory,
        array $data = []
    ) {
        $this->_productRepository = $productRepository;
        $this->_registry = $registry;
        $this->_urlBuilder = $urlBuilder;
        $this->_attributeRepository = $attributeRepository;
        $this->_productAttributeRepository = $productAttributeRepository;
        $this->imageFactory = $imageFactory;
        $this->_productImageHelper = $productImageHelper;
        parent::__construct($context, $data);
    }

    public function getCurrentProduct()
    {
        return $this->_registry->registry('current_product');
    }

    public function getRelatedProducts()
    {
        $product = $this->getCurrentProduct();
        if ($product) {
            return $product->getRelatedProducts();
        }
        return [];
    }

    public function getSimilarAttributeLabel()
    {
        $product = $this->getCurrentProduct();
        if ($product) {
            return $product->getCustomAttribute('similar_attribute_label');
        }
        return null;
    }

    public function getProductById($productId)
    {
        try {
            $product = $this->_productRepository->getById($productId);
            if ((int)$product->getStatus() !== \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                return null;
            }
            if (!$product->isSalable()) {
                return null;
            }
            return $product;

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    public function getProductUrl($product)
    {
        return $product->getUrlModel()->getUrl($product);
    }

    // public function getAttributeValue($productId , $attributeCode){
    //     $product = $this->_productRepository->getById($productId);
    //     return $attributeValue = $product->getAttributeText($attributeCode);
    // }

    public function getAttributeValue($productId , $attributeCode){
        try{
            $product = $this->_productRepository->getById($productId);
            $attributeValue = $product->getData($attributeCode);
            if (!empty($attributeValue)) {
                $attribute = $this->_productAttributeRepository->get($attributeCode);
                $options = $attribute->getOptions();
                foreach ($options as $option) {
                    if ($option->getValue() == $attributeValue) {
                        return $option->getLabel();
                    }
                }
            }
        }catch(\Exception $e){
            return false;
        }
    }

    // public function getProductImage($product){
    //     $imageHelper = $this->imageFactory->create()->init($product, self::SMALL_IMAGE);
    //     $imageUrl = $imageHelper->getUrl();
    //     return $imageUrl;
    // }

    public function getProductImage($product, $imageId, $width , $height )
    {
        // $product = $this->_productRepository->getById($product);
        $resizedImage = $this->_productImageHelper
                           ->init($product, $imageId)
                           ->constrainOnly(TRUE)
                           ->keepAspectRatio(TRUE)
                           ->keepTransparency(TRUE)
                           ->keepFrame(FALSE)
                           ->resize($width, $height);
        return $resizedImage;
    }  
}
