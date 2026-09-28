<?php
namespace ArtM\Common\Block;
 
use Magento\Framework\View\Element\Template;
 
class Productpriceblock extends Template
{
    public function __construct(Template\Context $context,                
                \Magento\Catalog\Model\Product $ProductModel,
                \Magento\Catalog\Block\Product\ListProduct  $ListProductBlock,
                \Magento\Eav\Model\Config $eavConfig,
                array $data = []
                )
    {
        $this->ProductModel = $ProductModel;
        $this->ListProductBlock  = $ListProductBlock;
        $this->eavConfig  = $eavConfig;

        parent::__construct($context, $data);
    }

    public function createHtml($pid){
        if ($this->ListProductBlock->getMode() == 'grid') {
            $templateType = \Magento\Catalog\Block\Product\ReviewRendererInterface::SHORT_VIEW;
        } else {
            $templateType = \Magento\Catalog\Block\Product\ReviewRendererInterface::FULL_VIEW;
        }
        $_product =  $this->ProductModel->load($pid);
        $reviewHtml =  $this->ListProductBlock->getReviewsSummaryHtml($_product, $templateType);
        if( $_product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                        
        }else{
            $regular_price = $_product->getPrice();
            $finalPriceAmt = $_product->getPriceInfo()->getPrice('final_price')->getValue();
            if( (int)$finalPriceAmt == (int)$regular_price){
                echo '<label>MRP: </label>';
                $class= 'mrp_here';
            }else{
                $class= '';
            }
            echo "<div class='price_html_category_page simple_product ".$class."'>";
            echo $this->ListProductBlock->getProductPrice($_product);
            echo "</div>";
        }
        $prd_id_for_tier_price = $_product->getId();
        if( $_product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){

                $_configChild = $_product->getTypeInstance()->getUsedProductIds($_product);
                $getChildId = array();
                $first_id = '';
                foreach ($_configChild as $child){
                    $getChildId[] = $child;
                }
                echo "<div class='simple_prd_array hide'>";
                print_r($getChildId);
                echo "</div>";
                if(!empty($getChildId)){
                    //$first_id = $getChildId[0];
                    //------------------------------- child with min price
                    $tmpparr = [];
                    foreach ($getChildId as $chl_id){
                         $_childProduct = $this->ProductModel->load($chl_id);                                           
                         $specialPrice = (int) $_childProduct->getFinalPrice();
                         $tmpparr[$_childProduct->getId()] =  $specialPrice;                                        
                     }
                     asort($tmpparr);
                     $first_id = array_keys($tmpparr)[0];
                    //---------------------------------
                }
                                                

                $prd_id_for_tier_price = $first_id;
                
               // $product_first_simple = $objectManager->create('Magento\Catalog\Model\Product')->load($first_id);
                $product_first_simple = $this->ProductModel->load($first_id);
                $sku = $product_first_simple->getSku()."<br>";
                
                
                $regular_price = $product_first_simple->getPrice();
                $finalPriceAmt = $product_first_simple->getPriceInfo()->getPrice('final_price')->getValue();
                if( (int)$finalPriceAmt == (int)$regular_price){
                    echo '<label>MRP: </label>';
                    $class= 'mrp_here';
                }else{
                    $class= '';
                }
                echo "<div class='price_html_category_page configurable_product ".$class."'>";
                echo $this->ListProductBlock->getProductPrice($product_first_simple);
                echo "</div>";
                
            }
            
            //echo $reviewHtml;
            //$product_obj = $objectManager->create('Magento\Catalog\Model\Product')->load($prd_id_for_tier_price);  
            $product_obj =   $this->ProductModel->load($prd_id_for_tier_price);              
            $tier_price = $product_obj->getTierPrice();
            $mrp = $product_obj->getPrice();
            if(count($tier_price) > 0){
                foreach($tier_price as $pirces){
                    $tier_qty = (int)$pirces['price_qty'];
                    $tier_price = (int)$pirces['website_price'];


                    $save_percentage = ceil(100 - ((100 / $mrp) * $tier_price));
                    echo "<div class='tier_price_category_page'>Buy ".$tier_qty." for <strong>₹".$tier_price."</strong> each and save <strong>".$save_percentage."%</strong></div>";

                }
            }
    }
    public function getSizes($cpid,$attrdata){   
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();        
        $StockState = $objectManager->get('\Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku');
        $attrName = $attrdata;    
        $attribute =  $this->eavConfig->getAttribute('catalog_product', $attrName); 
        $options = $attribute->getSource()->getAllOptions();
        $arr = [];
        foreach($options as $k=>$v){           
            $arr[$v['value']] = $v['label'];
        }
        //$product =  $this->ProductModel->load($cpid);
        $product=  $objectManager->create('\Magento\Catalog\Model\Product')->load($cpid);
         if($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
  
            /*$children = $product
            ->getTypeInstance()
            ->getChildrenIds($product->getId());
            // print_r($children);
            
            foreach ($children[0] as $child){
              $childProducts[] = $child;
           }
          // print_r($childProducts);*/
         
           $childrenids = [];
           $sizes = [];
           $_children = $product->getTypeInstance()->getUsedProducts($product);
          foreach($_children as $child){
            $qtyOfAllVarient = 0;   
              //$childrenids[] =  $child->getID();
              $pid =  $child->getID();
              //echo $pid."<br/>";
              $cproduct = $this->ProductModel->load($pid);
              $vsku = $cproduct->getSku();
              //----------------------------check if qty is 0
              $isManageStock = $this->ProductModel->getExtensionAttributes()->getStockItem()->getManageStock();
              if($isManageStock == 1){
                  $qtysrc = $StockState->execute($vsku);
                  $tqty = 0 ;
                  foreach($qtysrc as $src){
                      $tqty +=  $src['qty'];
                  }
                  $qtyOfAllVarient += $tqty; 
              }else{
                  $qtyOfAllVarient = 1;
              }    
              //-------------------------
              if($cproduct->getStatus() == 1 &&  ($qtyOfAllVarient > 0)){
               //------------------------
                $currentkey = $cproduct->getData($attrName);
                $shirt_size ='';
               //--------------------------
                //$shirt_size = $cproduct->getResource()->getAttribute($attrName)->getFrontend()->getValue($cproduct);
                if(array_key_exists($currentkey, $arr)){
                    $shirt_size =  $arr[$currentkey] ;
                }
                $shirt_size = trim((string) $shirt_size);
                if($shirt_size !=''){
                    $sizes[]  =  $shirt_size;
                }
              }
            }
            return $sizes;
        }
        return [];
    }
    public function check_partial_size_roll($complete_array , $current_array){
        $check_data_for_first = 0;
        $size_roll_available = array();
        $size_roll_available_html = '';
        $size_roll_available_html_temp = '';
        $to_embed = '';
        $flag_to_run_out_of_loop = '';
        
        $i = 0;
        $first = '';
        
        $j=0;
        foreach($complete_array as $complete_arr_val){
            $j++;
            //echo "</br>------</br>in loop.".$complete_arr_val;
            if (in_array($complete_arr_val, $current_array)){
                //echo "-found";
                if($i==0){
                    $first = $complete_arr_val;
                    $last = '';
                }else{
                    $last = $complete_arr_val;
                }
                
                
                $size_roll_available_html .= $complete_arr_val.",";
                $size_roll_available_html_temp .= $complete_arr_val.",";
                $i++;
                
            }else{
                if( $i > 2 ){
                    $curr_loop_value = $first." to ".$last;
                    
                    $size_roll_available_html = str_replace($size_roll_available_html_temp, $curr_loop_value, $size_roll_available_html).",";
                    $flag_to_run_out_of_loop = true;
                    
                }
                $i = 0;
                $size_roll_available_html_temp = '';
                
            }
            if( $j == count($complete_array) ){
                if( $i > 2 ){
                    $curr_loop_value = $first." to ".$last;
                    $size_roll_available_html = str_replace($size_roll_available_html_temp, $curr_loop_value, $size_roll_available_html);
                }
            }
        }
            return $size_roll_available_html;
        
    }
    
    public function check_roll($complete_array , $current_array){
        $return_vals = array();
        $return_vals['roll_length'] = '';
        $return_vals['text_value'] = '';
        
        
        //send available sizes
        if($complete_array === $current_array){
            
            $firstKey = reset($current_array);
            $lastKey = end($current_array);
            $return_vals['roll_length'] = "full";
            $return_vals['text_value'] = $firstKey." to ".$lastKey;
            
        }else{
            $return_vals['roll_length'] = "partial";

        // $diff = array_diff($complete_array , $current_array);

            $return_vals['text_value'] = $this->check_partial_size_roll($complete_array , $current_array);
        }
        
        
        $return_vals['text_value'] = rtrim($return_vals['text_value'], ",");
        return $return_vals;
    }
    public function getSizerollfromDb($prdid){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $select = $connection->select()->from('artm_sorting_products')
        ->where('product_id = ?', $prdid)
        ->order('id DESC')
        ->limit(1);
        $result = $connection->fetchRow($select);
        if($result){
            return $result;
        }else{
            return [];
        }
    }
}
?>