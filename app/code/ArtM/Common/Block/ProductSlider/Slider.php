<?php
namespace ArtM\Common\Block\ProductSlider;

use Magento\Framework\App\CacheInterface;
class Slider extends \Magento\Framework\View\Element\Template
{	
    protected $pricehelper;
    protected $category;
    protected $productcollection;
    protected $orderRepository;
    protected $productobject;
    protected $_productRepository;
    protected $resultJsonFactory;
    protected $cache;

      public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Catalog\Model\ProductRepository $_productRepository,
        \Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Model\Product $productobject,
        \Magento\Framework\Pricing\Helper\Data $pricehelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productcollection,
        CacheInterface $cache
    ){
        $this->pricehelper = $pricehelper;
        $this->category = $category;  
        $this->productcollection = $productcollection;  
        $this->orderRepository = $orderRepository;
        $this->productobject = $productobject;
		$this->_productRepository = $_productRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cache = $cache;
		parent::__construct($context);
    }
    
    
	public function createSlider($heading, $catId, $numProduct)
    {
        if ($cachedHtml = $this->getFromCache($catId)) {
            return $cachedHtml;
        }

        // Initialize the result variables
        $prd_html = '';
        $html = '';
        $cat_url = '';
        
        $collection = $this->productcollection
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        if ($catId === '0') {
            $collection->setPageSize(20)
                ->setOrder('entity_id', 'DESC');
        } else {
            $_category = $this->category->load($catId);
            $cat_url = $_category->getUrl()."#".$catId;
            $collection->addCategoryFilter($_category)
                ->setPageSize($numProduct)
                ->setOrder('position', 'ASC');
        }

        $collection->load();
        foreach ($collection as $product) {
            $prd_html .= $this->getsliderProducts($product->getId());
        }

        $html = $this->slider_html($heading, $cat_url, $prd_html);
        $this->saveToCache($catId, $html);

        return $html;
    }
    public function getProductSizeroll($prd_id){
        $outputs ='';
        $product = $this->_productRepository->getById( $prd_id );
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $isSizerollEnabled = $objectManager->get('ArtM\Common\Helper\Data')->getConfig('common/sizeroll/common_sizeroll');
        $attributeSet = $objectManager->create('Magento\Eav\Api\AttributeSetRepositoryInterface');
        $sizerollHelper = $objectManager->get('\ArtM\Sizeroll\Helper\Data');
        $cBlock = $objectManager->get('ArtM\Common\Block\Productpriceblock');
        $attributeSetRepository = $attributeSet->get($product->getAttributeSetId());
        $attribute_set_name = $attributeSetRepository->getAttributeSetName();
        $p_brand_name = $product->getResource()->getAttribute('brand')->getFrontend()->getValue($product) ;
        $p_brand_name = trim((string) $p_brand_name);
        $p_brand_name = strtolower((string) $p_brand_name);
       
        $sizeroll_data = $sizerollHelper->getrow( strtolower($attribute_set_name), $p_brand_name );
            if($isSizerollEnabled && (isset($sizeroll_data[0])))
            { 
                $attrdata = $sizeroll_data[0]['attribute'];
                $arraydata = $sizeroll_data[0]['totalsizedarrayvalue'];
                $sizes = $cBlock->getSizes($prd_id,$attrdata);
                $complete_array = explode(",",$arraydata);
                $complete_array = array_map('strtolower', $complete_array);
                $complete_array = array_map('trim', $complete_array);
            
                if(!empty($sizes)){
                    $sizes = array_map('strtolower', array_unique($sizes));
                    $result = $cBlock->check_roll($complete_array , $sizes);
                    $output = str_replace('to','<span style="font-size: 10px;text-transform: lowercase;">to</span>',$result['text_value']);
                    $outputs .= '<div class="sizerolldiv_label" style="color:#b2b2b2;">Sizes:&nbsp;<span class="sizerolldiv">'.$output.'</span></div>';        
                }
            } 
            return $outputs;          
    }
    public function getsliderProducts($prd_id){
        $sizerollhtml = $this->getProductSizeroll($prd_id);
        $pricehtml = '';
        $product = $this->_productRepository->getById( $prd_id );
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $templateType = \Magento\Catalog\Block\Product\ReviewRendererInterface::SHORT_VIEW;
        
        
        $productUrl = $product->getProductUrl();
        $brandname = $product->getResource()->getAttribute('brand')->getFrontend()->getValue($product);
        
        
        if( $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){

            $prd_id_for_tier_price = $product->getId();
            $pricehtml.= "<div class='custom_code configurable_prd'>";

            $_configChild = $product->getTypeInstance()->getUsedProductIds($product);
            $getChildId = array();
            $first_id = '';
            foreach ($_configChild as $child){
                $getChildId[] = $child;
            }
            if(!empty($getChildId)){
                //$first_id = $getChildId[0];
                //------------------------------- child with min price
                $tmpparr = [];
                foreach ($getChildId as $chl_id){
                     $_childProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($chl_id);                                           
                     $specialPrice = (int) $_childProduct->getFinalPrice();
                     $tmpparr[$_childProduct->getId()] =  $specialPrice;                                        
                 }
                 asort($tmpparr);
                 $first_id = array_keys($tmpparr)[0];
                //---------------------------------
            }

            $prd_id_for_tier_price = $first_id;
//            $pricehtml.= $first_id;
           // $product_first_simple = $this->productobject->load($first_id);
            $product_first_simple = $objectManager->create("\Magento\Catalog\Model\Product")->load($first_id);

            
            $regular_price = (int)$product_first_simple->getPrice();
            
            $finalPriceAmt = (int)$product_first_simple->getPriceInfo()->getPrice('final_price')->getValue();
            
            $pricehtml .= $this->getcustomPriceHtml($regular_price, $finalPriceAmt, $prd_id);
            
            $product->getReviewsSummaryHtml($product, $templateType);

            $product_obj = $this->productobject->load($prd_id_for_tier_price);                
            $tier_price = $product_obj->getTierPrice();
            $mrp = $product_obj->getPrice();
            if(count($tier_price) > 0){
                foreach($tier_price as $pirces){
    //                                        var_dump( $pirces );

                    $tier_qty = (int)$pirces['price_qty'];
                    $tier_price = (int)$pirces['website_price'];


                    $save_percentage = ceil(100 - ((100 / $mrp) * $tier_price));
                    $pricehtml.= "<div class='tier_price_category_page'>Buy ".$tier_qty." for <strong>₹".$tier_price."</strong> each and save <strong>".$save_percentage."%</strong></div>";

                }
            }
            $pricehtml.= "</div>";
        }else{
            $regular_price = (int)$product->getPrice();
            
            $finalPriceAmt = (int)$product->getPriceInfo()->getPrice('final_price')->getValue();
            
            $pricehtml .= $this->getcustomPriceHtml($regular_price, $finalPriceAmt, $prd_id);
            
        }
        
        $helperImport = $objectManager->get('\Magento\Catalog\Helper\Image');
                    
        $imageUrl = $helperImport->init($product, 'product_page_image_small')
                ->setImageFile($product->getSmallImage())
                ->resize(380)
                ->getUrl();
        
        $SliderPrdhtml = '
                    <li class="item product product-item">
                        <div class="product-item-info" data-container="product-grid">
                            <a href="'.$productUrl.'" class="product photo product-item-photo" tabindex="-1">
                                <span class="product-image-container product-image-container-15362" style="width: 320px;">
                                    <span class="product-image-wrapper" style="padding-bottom: 125%;">
                                        <img class="product-image-photo" loading="lazy" src="'.$imageUrl.'" alt="'.$product->getName().'" loading="lazy">  
                                    </span>
                                </span>
                            </a>
                            <div class="product details product-item-details">
                                <div class="brand_name">
                                    '.$brandname.'                        
                                </div>
                                <strong class="product name product-item-name">
                                    <a class="product-item-link" title="Callaway Chrome Soft Golf Balls" href="'.$productUrl.'">
                                        '.str_ireplace( $brandname , '' , $product->getName()).'
                                    </a>
                                </strong>
                                '.$pricehtml.$sizerollhtml.'
                            </div>

                        </div>
                    </li>';
        return $SliderPrdhtml;
    }
    public function getcustomPriceHtml($regular_price , $finalPriceAmt , $prd_id){
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //$priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');
        $priceHelper = $this->pricehelper;
        
        $pricehtml = '';
        $pricehtml .= "<div class='custom_code simple_prd'>";
        if( (int)$finalPriceAmt == (int)$regular_price){
            $pricehtml .= '<span class="price-label">'.$this->getlabel("mrp").'</span>';
            $class= 'mrp_here';
            $pricehtml .= '<div class="price_html_category_page configurable_product mrp_here">
                <div class="price-box price-final_price" data-role="priceBox" data-product-id="'.$prd_id.'" data-price-box="product-id-'.$prd_id.'">
                    <span class="price-container price-final_price tax weee">
                        <span id="product-price-'.$prd_id.'" data-price-amount="'.(int)$finalPriceAmt.'" data-price-type="finalPrice" class="price-wrapper "><span class="price">'.$priceHelper->currency( (int)$finalPriceAmt, true, false).'</span></span>
                    </span>
                </div>
            </div>';
        }else{

            $save_percentage = ceil(100 - ((100 / (int)$regular_price) * (int)$finalPriceAmt));
            $class= '';
            $pricehtml .= '<div class="price-box price-final_price" data-role="priceBox" data-product-id="'.$prd_id.'" data-price-box="product-id-'.$prd_id.'">
                <span class="old-price">
                    <span class="price-container price-final_price tax weee">
                        <span class="price-label">'.$this->getlabel("mrp").'</span>
                        <span id="old-price-'.$prd_id.'" data-price-amount="'.(int)$regular_price.'" data-price-type="oldPrice" class="price-wrapper "><span class="price">'.$priceHelper->currency((int)$regular_price, true, false).'</span></span>
                    </span>
                </span>
                <div class="break_here"></div>
                <span class="special-price">
                    <span class="price-container price-final_price tax weee">
                        <span class="price-label">'.$this->getlabel("deal_price").'</span>
                        <span id="product-price-'.$prd_id.'" data-price-amount="'.(int)$finalPriceAmt.'" data-price-type="finalPrice" class="price-wrapper "><span class="price">'.$priceHelper->currency((int)$finalPriceAmt, true, false).'</span></span>
                    </span>
                </span>
                <div class="break_here"></div>
                <div class="you-save-statement">
                    <span class="label_special_price_custom yousave_price_label">You save:</span>
                    <span class="yousave_price">
                        <span>
                            '.$priceHelper->currency( ((int)$regular_price - (int)$finalPriceAmt) , true, false).'                
                        </span>
                        <span>
                            ('.$save_percentage.'% OFF)
                        </span>
                    </span>
                </div>
            </div>';
        }
        $pricehtml .= "</div>";
        return $pricehtml;
    }
    public function slider_html($heading, $cat_url, $prd_html){
        
        $sliderHtml = '
        <div class="container-fluid product_slider_container">
            <div class="container-fluid product_container_inside">
                <div class="header_content_product_slider">
                    <div class="header_content_product_slider_inside">
                        <div class="heading_product_slider">
                            <div class="heading_golfoy left">
                                <h2>
                                    '.$heading.'
                                </h2>
                            </div>
                        </div>
                        <div class="header_link_product_slider">
                            <a href="'.$cat_url.'">
                                View all
                            </a>
                        </div>
                    </div>
                </div>
                <div class="product_content_product_slider">
                    <div class="product_content_product_slider_inside">
                        <div class="slider_arrows">
                            <div class="left_arrow">
                            </div>
                            <div class="right_arrow">
                            </div>
                        </div>
                        <div class="product_content_product_slider_before_list">
                            <ol class="products list items product-items">
                                '.$prd_html.'
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        return $sliderHtml;
    }
    
    /**
     * Store HTML content in cache
     *
     * @param string $categoryId
     * @param string $htmlContent
     * @return void
     */
    public function saveToCache(string $categoryId, string $htmlContent)
    {
        $cacheKey = $this->generateCacheKey($categoryId);
        $this->cache->save($htmlContent, $cacheKey, ['PRODUCT_HTML_CACHE'], 259200); // 3-day cache lifetime
    }

    /**
     * Retrieve HTML content from cache
     *
     * @param string $categoryId
     * @return string|null
     */
    public function getFromCache(string $categoryId): ?string
    {
        $cacheKey = $this->generateCacheKey($categoryId);
        return $this->cache->load($cacheKey);
    }

    /**
     * Generate a unique cache key
     *
     * @param string $categoryId
     * @return string
     */
    public function generateCacheKey(string $categoryId): string
    {
        return 'CATEGORY_HTML_' . $categoryId;
    }

}