<?php

namespace ArtM\Optimization\Block\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use ArtM\Optimization\Api\ProductCacheRepositoryInterface;
use ArtM\Optimization\Model\ProductCacheFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use ArtM\RecommendedFilter\Model\ArtmSortingProductsFactory;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    protected $_customerSession;
    protected $categoryFactory;
    protected $priceHelperData;
    protected $productCacheRepository;
    protected $productCacheFactory;
    protected $artmSortingProducts;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * ListProduct constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        ProductRepositoryInterface $productRepository,
        ProductCacheRepositoryInterface $productCacheRepository,
        ProductCacheFactory $productCacheFactory,
        ScopeConfigInterface $scopeConfig,
        Data $priceHelperData,
        ArtmSortingProductsFactory $artmSortingProducts,
        array $data = [],
    ) {
        $this->_customerSession = $customerSession;
        $this->categoryFactory = $categoryFactory;
        $this->productRepository = $productRepository;
        $this->pricingHelper = $priceHelperData;
        $this->productCacheRepository = $productCacheRepository;
        $this->productCacheFactory = $productCacheFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->artmSortingProducts = $artmSortingProducts;
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );

    }
    
    /**
     * Get tier price HTML.
     *
     * @param Product $product
     * @return string
     */
    public function getTierPriceHtml(Product $product)
    {
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            return $this->getSimpleProductTierPriceHtml($product);
        } elseif ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return $this->getConfigurableProductTierPriceHtml($product);
        }

        return '';
    }

    /**
     * Get tier price HTML for a simple product.
     *
     * @param Product $product
     * @return string
     */
    protected function getSimpleProductTierPriceHtml(Product $product)
    {
        $tierPrices = $product->getTierPrices();
        if (!empty($tierPrices)) {
            $lowestTierPrice = min(array_map(function($tierPrice) {
                return $tierPrice->getValue();
            }, $tierPrices));
            $lowestTierQty = min(array_map(function($tierPrice) {
                return $tierPrice->getQty();
            }, $tierPrices));
            $finalPrice = $product->getFinalPrice();
            $savePercentage = round((($finalPrice - $lowestTierPrice) / $finalPrice) * 100, 2);

            return "<div class='tier_price_category_page'>Buy " . (int)$lowestTierQty . " for <strong>" . $this->pricingHelper->currency($lowestTierPrice, true, false) . "</strong> each and save <strong>" . $savePercentage . "% more</strong></div>";
        }

        return '';
    }

    /**
     * Get tier price HTML for a configurable product.
     *
     * @param Product $product
     * @return string
     */
    protected function getConfigurableProductTierPriceHtml(Product $product)
    {
        $lowestTierPrice = null;
        $lowestTierQty = null;
        $finalPrice = $product->getFinalPrice();
        
        foreach ($product->getTypeInstance()->getUsedProducts($product) as $simpleProduct) {
            $tierPrices = $simpleProduct->getTierPrices();
            if (!empty($tierPrices)) {
                foreach ($tierPrices as $tierPrice) {
                    if ($lowestTierPrice === null || $tierPrice->getValue() < $lowestTierPrice) {
                        $lowestTierPrice = $tierPrice->getValue();
                        $lowestTierQty = $tierPrice->getQty();
                    }
                }
            }
        }
        try{
            if ($lowestTierPrice !== null && $lowestTierQty !== null && $finalPrice > 0) {
                $savePercentage = round((($finalPrice - $lowestTierPrice) / $finalPrice) * 100, 2);
    
                return "<div class='tier_price_category_page'>Buy " . (int)$lowestTierQty . " for <strong>" . $this->pricingHelper->currency($lowestTierPrice, true, false) . "</strong> each and save <strong>" . $savePercentage . "% more</strong></div>";
            }
        } catch(\Exception $e){
            return '';
        }
        return '';
    }

    public function getSimpleProduct($product){
        foreach ($product->getTypeInstance()->getUsedProducts($product) as $simpleProduct) {
            return $simpleProduct;
        }
    }

    public function checkAndUpdateProductCacheData($_product)
    {
        $productId = $_product->getId();
        try {

            $productCache = $this->productCacheRepository->getByProductId($productId);

            $lastModify = $productCache->getLastModify();
            $currentTime = new \DateTime();
            $lastModifyTime = new \DateTime($lastModify);
            $interval = $currentTime->diff($lastModifyTime);

            if ($interval->h >= 12 || $interval->days > 0) {
                $this->productCacheRepository->delete($productCache);

                $productCache = $this->productCacheFactory->create();
                $productCache->setProductId($productId);
                $productCache->setPriceHtml($this->getPriceHtml($_product));
                $productCache->setTierPriceHtml($this->getTierPriceHtml($_product));
                $productCache->setLastModify((new \DateTime())->format('Y-m-d H:i:s'));
                $this->productCacheRepository->save($productCache);
                
                $attributesArray = ['offer_status','newarrivals','can_print','playerschoice','limitedstock','bestseller','newarrivals','waterproof','waterrepel'];
                $this->getAllAttributesValue($attributesArray, $_product);

            }
        } catch (NoSuchEntityException $e) {

            $productCache = $this->productCacheFactory->create();
            $productCache->setProductId($productId);
            $productCache->setPriceHtml($this->getPriceHtml($_product));
            $productCache->setTierPriceHtml($this->getTierPriceHtml($_product));
            $productCache->setLastModify((new \DateTime())->format('Y-m-d H:i:s'));
            
            $this->productCacheRepository->save($productCache);
            
            $attributesArray = ['offer_status','newarrivals','can_print','playerschoice','limitedstock','bestseller','newarrivals','waterproof','waterrepel'];
            $this->getAllAttributesValue($attributesArray, $_product);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function getPriceHtmlFromCache($_productid){
        $productCache = $this->productCacheRepository->getByProductId($_productid);
        return $productCache->getPriceHtml();
    }


    public function getTierPriceHtmlFromCache($_productid){
        $productCache = $this->productCacheRepository->getByProductId($_productid);
        return $productCache->getTierPriceHtml();
    }

    /**
     * Get all product cache data.
     *
     * @return array
     */
    public function getAllProductCacheData()
    {
        try {
            $productCacheData = $this->productCacheRepository->getAllData();
            return $productCacheData;
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }

    public function getPriceHtml($_product){
        $_htmlPrice = '';
        if ($_product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $_simpleproduct = $this->getSimpleProduct($_product);
            if ($_simpleproduct !== null){
                $_htmlPrice = "<div class='price_html_category_page configurable_product'>";
                $_htmlPrice .= $this->getProductPrice($_simpleproduct);
                $_htmlPrice .= "</div>";
            }
        }else{
            $_htmlPrice .= "<div class='price_html_category_page configurable_product'>";
            $_htmlPrice .= $this->getProductPrice($_product);
            $_htmlPrice .= "</div>";
        }
        return $_htmlPrice;
    }

    public function getAllAttributesValue($attributeCode, $_product)
    {
        try{
            $productCache = $this->productCacheRepository->getByProductId($_product->getId());
            $attributeValue = [];
            foreach($attributeCode as $attrCode){
                $attributeValue[$attrCode] = $this->getAttributesValueByAttributesCode($_product , $attrCode);
            }
            $productCache->setAttributesHtml(json_encode($attributeValue));
            $this->productCacheRepository->save($productCache);
        }catch(NoSuchEntityException $e){
            return null;
        }
    }

    public function getAttributesValueByAttributesCode($_product , $attrCode){
        $attributeText = $_product->getData($attrCode);
        return $attributeText;
    }
    
    public function getCacheValueFromTable($attrCode , $_productId ){
        try{
            $productCache = $this->productCacheRepository->getByProductId($_productId);
            $allAttributesValue = json_decode($productCache->getAttributesHtml());
            return $allAttributesValue->$attrCode;
        }catch(NoSuchEntityException $e){
            return null;
        }catch(\Exception $e){
            return null;
        }
    }

    public function getCacheStatus(){
        return $this->_scopeConfig->getValue(
            'optimization/category_status/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPointData($productId){
        $artmSortingProductModel = $this->artmSortingProducts->create();
        $item = $artmSortingProductModel->getCollection()
            ->addFieldToFilter('product_id', $productId)
            ->addFieldToSelect([
                'updated_at',
                'high_discount_points',
                'availability_other',
                'availability_cgc',
                'availability_golfoy',
                'is_feature',
                'total_points',
                'size_roll_points',
                'created_date_points'
            ])
            ->getFirstItem();
        return $item->getData();
    }

    public function getCustomOptionPrice($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
            $options = $product->getOptions();
            if (empty($options)) {
                return '';
            }
            $prices = [];
            foreach ($options as $option) {
                $prices[] = (float)$option['price'];
            }
            
            $zeroCount = count(array_filter($prices, function ($price) {
                return $price == 0.0;
            }));
            
            if ($zeroCount === count($prices)) {
                return 'free';
            } elseif ($zeroCount > 0) {
                return 'partial_free';
            } else {
                return 'not_free';
            }
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }
}