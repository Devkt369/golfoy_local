<?php
namespace ArtM\Common\Block;
 
use Magento\Framework\View\Element\Template;
 
class Subcat extends Template
{
    public function __construct(Template\Context $context,                
                \Magento\Catalog\Model\Category $CategoryModel,
                \Magento\Catalog\Helper\Image $helperImage,               
                \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
                \ArtM\Common\Model\CustomcacheFactory $customcache,
                array $data = []
                )
    {
        parent::__construct($context, $data);
        $this->CategoryModel = $CategoryModel;
        $this->helperImage = $helperImage;
        $this->productCollectionFactory = $productCollectionFactory;        
        $this->_customcache = $customcache;        
    }

    public function createHtml($catId, $subcatConf, $type, $page_type){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
         
        $customcache_model = $this->_customcache->create();
        
        $customcache = $customcache_model
            ->getCollection()
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('page_type', $page_type)
            ->addFieldToFilter('cat_id', $catId);
        
            
            if( count($customcache) > 0 ){
            $outHtml = '';
            foreach($customcache as $item){
                $outHtml =  $item->getContent();
                break;
            }
        echo $outHtml;   
        }else{
            $cdata = explode('-',$subcatConf);
            $pcount = count($cdata);

            $subCategory = $this->CategoryModel->load($catId);
            $subCats = $subCategory->getChildrenCategories();


            for($i= 0;$i<$pcount;$i++){
                $subcat_id = 0;
                $c=1;
                foreach ($subCats as $subcat) {
                    if($c == $cdata[$i]){
                    $subcat_id = $subcat->getId();
                    break;
                    }
                    $c++;
                }
                if($subcat_id != 0){
                $subCategory = $this->CategoryModel->load($subcat_id);
                $subCats = $subCategory->getChildrenCategories();}
            }
            $dataarray  = [];
            if($subcat_id != 0){
                foreach ($subCats as $subcat) {
                    $_category = $this->CategoryModel->load($subcat->getId());
                    $subcaturl = $subcat->getUrl();
                    $_imgHtml = '';



                    $productCollection = $this->productCollectionFactory->create();
                    $collection = $productCollection
                            ->addAttributeToSelect('*')
                            ->addCategoryFilter($_category)
                            ->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
                            ->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                            ->setPageSize(5) 
                            ->setCurPage(1)
                            ->load();


                    $count = $collection->count();
                    if($count > 0){
                    $imageUrl = '';    
                    foreach ($collection as $product){
                    //    echo 'Name  =  '.$product->getName().'<br>';
                    //    echo 'Id  =  '.$product->getId().'<br>';   
                   /*  $imageUrl = '/media/catalog/product'.$product->getData('thumbnail');*/
                       $imageUrl = $this->helperImage->init($product, 'product_page_image_small')
                                    ->setImageFile($product->getSmallImage()) // image,small_image,thumbnail
                                    ->resize(90)
                                    ->getUrl();
                        break;
                    }  
                    $imageUrl = str_replace("cdn.golfoy.com","golfoy.com",$imageUrl);
                    $dataarray[] = ['link' => $subcaturl, 'img' => $imageUrl, 'name'=> $subcat->getName()];

                    }
                }
            }
            $html = "";
            if(!empty($dataarray)){ 
                // $width = (count($dataarray) * 140).'px'; 
                $html .= '<div class="col-xs-12 inner_width_js" style= "margin:0 auto;">';
                foreach($dataarray as $v){
                    $html .= '<div class="subcategory_top_category col-md-2 col-sm-4 col-xs-6 blocks_inside_js">';
                    $html .= '<a class="link" href="'.$v['link'].'">';
                    $html .= '<div class="image_prd_of_cat">';
                    $html .= '<img width = "90" height="90" alt="category icon" src="'.$v['img'].'" />';
                    $html .= '</div>';
                    $html .= '<div class="subcat_name_of_category">'.$v['name'].'</div>';
                    $html .= '</a>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                echo $html;
                
                
                $customcache_model->setData([
                    "type" => $type,
                    "page_type" => $page_type,
                    "content" => $html,
                    "cat_id" => $catId
                ]);
                $customcache_model->save();
                    
            }       
        }
    }
}
?>