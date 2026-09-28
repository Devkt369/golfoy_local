<?php

namespace ArtM\Sortbyoptions\Model\Elasticsearch\Adapter\DataMapper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class AttributesToSort
{
    private $resource;
    private $storeManager;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
    }
    // get rating value and reviews count 
    private function getRCount($entityId, $storeId){
        $sql = "SELECT `reviews_count`, `rating_summary` FROM `review_entity_summary` WHERE entity_pk_value = '".$entityId."' AND store_id = '".$storeId."'";
        $rcount = $this->resource->getConnection()->fetchAll($sql);
        if(!empty($rcount)){
            $reviewscount = $rcount[0]['reviews_count'];
            $reviewval = $rcount[0]['rating_summary'];
        }else{
            $reviewscount = 0;
            $reviewval = 0;
        } 
        return ['cnt'=> $reviewscount, 'val'=> $reviewval];
    }  
    // get created date  
    private function getCreated($entityId, $storeId){
        $sql = "SELECT `created_at` FROM `catalog_product_entity` WHERE entity_id = '".$entityId."'";
        $rvcount = $this->resource->getConnection()->fetchCol($sql);
        $created = '1970-01-01 00:00:00';
        if(!empty($rvcount)){
            $created= $rvcount[0];
        }
        return strtotime($created);
    }  
    private function getMinPrice($arr){        
        $min = 0;
        $prices = [];   
        foreach($arr as $v){
            $prices[] =  $v['sp'];
        }
        $min = min($prices);
        return $min;                                                                                           return $min;
    }
    private function simpleProductPrice($ids){ 
        $ids = implode(', ',  $ids);
        $result = [];
        $simpleBatch = "SELECT DISTINCT e.entity_id as id, p.value as price, sp.value as special_price,
        fd.value as from_date, td.value as to_date FROM catalog_product_entity  e
        INNER JOIN catalog_product_entity_int s ON e.entity_id = s.entity_id AND s.attribute_id = 96
        LEFT JOIN catalog_product_entity_decimal p ON e.entity_id = p.entity_id AND p.attribute_id = 75 
        AND p.store_id = 0
        LEFT JOIN catalog_product_entity_decimal sp ON e.entity_id = sp.entity_id AND sp.attribute_id = 76 
        AND sp.store_id = 0
        LEFT JOIN catalog_product_entity_datetime fd ON e.entity_id = fd.entity_id AND fd.attribute_id = 77 
        AND fd.store_id = 0
        LEFT JOIN catalog_product_entity_datetime td ON e.entity_id = td.entity_id AND td.attribute_id = 78 
        AND td.store_id = 0
        WHERE e.entity_id IN ($ids) AND s.value = 1 AND p.value IS NOT NULL
        ORDER BY `id` ASC";
        $entityIdsall = $this->resource->getConnection()->fetchAll($simpleBatch);
            foreach($entityIdsall as $parr){        
                $price = $final_price = 0;
                $id = $parr['id'];
                $price = $parr['price'];
                $final_price =  $price;
                $specialPrice = $parr['special_price'];
                if(($specialPrice != '') && $specialPrice < $price){
                    $specialfromdate = $parr['from_date'];
                    $specialtodate = $parr['to_date'];
                    $today = time();
                    if (is_null($specialfromdate) && is_null($specialtodate)){ 
                        $final_price =  $specialPrice;
        
                    }elseif(!is_null($specialfromdate) && is_null($specialtodate)){
                        if($today >= strtotime($specialfromdate))
                            $final_price =  $specialPrice;
                    }
                    elseif(is_null($specialfromdate) && !is_null($specialtodate)){
                        if($today <= strtotime($specialtodate))
                            $final_price =  $specialPrice;
                    }else{
                        if($today >= strtotime($specialfromdate) && $today <= strtotime($specialtodate))
                        $final_price =  $specialPrice;
                    }
                }            
                $result[$id] = ['p' => $price, 'sp' => $final_price];          
            }
            return $result;
    }
      // get discount percentage 
      private function CalDiscount($entityId){
        $type = 'simple';
        $discountpercent = 0;
        $sqlsimple = "SELECT DISTINCT type_id FROM catalog_product_entity WHERE entity_id = '".$entityId."'";
        $resimple = $this->resource->getConnection()->fetchCol($sqlsimple);
        if(!empty($resimple)){
            $type = $resimple[0];
        }
        if($type == 'configurable'){
            $confsql = "SELECT DISTINCT child.entity_id as cid FROM catalog_product_entity  e
             JOIN catalog_product_entity_int s ON e.entity_id = s.entity_id AND s.attribute_id = 96
            JOIN catalog_product_relation link ON link.parent_id = e.entity_id
            JOIN catalog_product_entity child ON child.entity_id = link.child_id
            WHERE e.entity_id = '".$entityId."' AND s.value = 1";
            $chids = $this->resource->getConnection()->fetchCol($confsql);
            
            if(!empty($chids)){               
                $pricearr = $this->simpleProductPrice($chids);                
                $firstKey = array_key_first($pricearr);
                $pr = $pricearr[$firstKey]['p'];
                $fpr = $pricearr[$firstKey]['sp'];
                if($pr > $fpr){
                    $percent = (($pr - $fpr) / $pr) * 100;
                    $discountpercent =  (int) ($percent);
                }
            }
        }else{
            $chids = [$entityId];            
            $pricearr = $this->simpleProductPrice($chids);
            if(isset($pricearr[$entityId])){           
            $pr = $pricearr[$entityId]['p'];
            $fpr = $pricearr[$entityId]['sp'];
                if($pr > $fpr){
                    $percent = (($pr - $fpr) / $pr) * 100;
                    $discountpercent =  (int) ($percent);
                }
            }
        }
        return $discountpercent;
    }
    private function CalPrice($entityId){
        $cprice = 0;
        $sqlsimple = "SELECT DISTINCT type_id FROM catalog_product_entity WHERE entity_id = '".$entityId."'";
        $resimple = $this->resource->getConnection()->fetchCol($sqlsimple);
        if(!empty($resimple)){
            $type = $resimple[0];
        }
        if($type == 'configurable'){           
            $confsql = "SELECT DISTINCT child.entity_id as cid FROM catalog_product_entity  e
             JOIN catalog_product_entity_int s ON e.entity_id = s.entity_id AND s.attribute_id = 96
            JOIN catalog_product_relation link ON link.parent_id = e.entity_id
            JOIN catalog_product_entity child ON child.entity_id = link.child_id
            WHERE e.entity_id = '".$entityId."' AND s.value = 1";
            $chids = $this->resource->getConnection()->fetchCol($confsql);
            if(!empty($chids)){              
                $pricearr = $this->simpleProductPrice($chids);
                return ($this->getMinPrice($pricearr) *100);
            }          
        }else{
            $chids = [$entityId];
            $pricearr = $this->simpleProductPrice($chids);
            if(!empty($pricearr)){
            return ($this->getMinPrice($pricearr) *100);
            }
        }
        return $cprice;
    }
    private function CalPoints($entityId, $categoryId){
        $spoints = 0;
        
        $select = $this->resource->getConnection()->select()->from('catalog_category_product')
        ->where('product_id = ?', $entityId)
        ->order('sorting_points DESC')
        ->limit(1);
         
        if($categoryId != ''){
            $select = $this->resource->getConnection()->select()->from('catalog_category_product')
            ->where('product_id = ?', $entityId)
            ->where('category_id = ?', $categoryId)
            ->limit(1);
        }

        $result = $this->resource->getConnection()->fetchRow($select);
        if($result){
            return $result['sorting_points'];
        }
        return $spoints;
    }
    // add in sortinglist array
    public function map($entityId, $storeId, $categoryId)
    {
        $attrToSort = [];
        $rdata = $this->getRCount($entityId, $storeId);
        $ceatedstr = (int) $this->getCreated($entityId, $storeId);
        $discount = (int) $this->CalDiscount($entityId);
        $customprice = (int) $this->CalPrice($entityId);
        $reviewscount = (int) $rdata['cnt'];
        $reviewval = (int) $rdata['val'];
        $spoints = (int) $this->CalPoints($entityId, $categoryId); 
        $attrToSort['crating'] = $reviewval;      
        $attrToSort['cratingc'] = $reviewscount;  
        $attrToSort['created'] = $ceatedstr;  
        $attrToSort['discount'] = $discount;
        $attrToSort['customprice'] = $customprice;   
        $attrToSort['crecommend'] = $spoints;  
        return $attrToSort;
    }
}
?>