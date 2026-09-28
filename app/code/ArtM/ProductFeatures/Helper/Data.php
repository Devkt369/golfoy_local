<?php
namespace ArtM\ProductFeatures\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{    
    protected $_storeManager;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,        
        \Magento\Store\Model\StoreManagerInterface $_storeManager
    )
    {
        $this->_storeManager = $_storeManager; 
    }
    
    public function getIcon($attr, $val){
        $img = strtolower($attr."_".$val);
        $img = str_replace(" ", "_", $img);
        $img = str_replace("/", "_", $img);
        $img = str_replace("-", "_", $img);
        $img = str_replace(" & ", "_", $img);
        $img = str_replace("’", "_", $img);
        //$url = "https://cdn.golfoy.com/pub/media/product_features/".$img.".svg";
        $url = $this->_storeManager->getStore()->getBaseUrl()."pub/media/product_features/".$img.".svg";
        $icon = '<img src="'.$url.'">';
        return $icon;
    }
    public function getText($attr, $val){
         return $val;
    }   
    public function prepareHtml($attr, $val, $data){
        $output = '';
        if($data != ''){
            $tag = strtolower($attr."_".$val);
            $tag = str_replace(" ", "_", $tag);
            
            $tag = str_replace("/", "_", $tag);
            $tag = str_replace("-", "_", $tag);
            $tag = str_replace(" & ", "_", $tag);
            $tag = str_replace("’", "_", $tag);
            
            $start = "start_".$tag;
            $end =  "end_".$tag ;
            
            $text =  explode($start,  $data);
            if(isset($text[1])){
                $text1 =  explode($end,  $text[1]);
                $output = $text1[0];
            }
        }
        return $output;
    }
    public function getSelecthtml($label, $attr, $val, $blockhtml){
        $icon = $this->getIcon($attr, $val);
        $text = $this->getText($attr, $val);
        
        $tooltip = $this->prepareHtml($attr, $val, $blockhtml);
        $tooltiptext = '';
        if($tooltip !=''){
        $tooltiptext = '<button class="featureinfoicon" >        
        <span class="glyphicon glyphicon-info-sign ballicon"></span></button>
        <span class="tooltiptexts">'.$tooltip.'</span>';
        }
        $html ="<center>$icon<br/><span class='label'>$label $tooltiptext</span><br/>";
        $html .="<span class='text'>$text</span></center>"; 
        return $html;        
    }
    public function getBoolhtml($label, $attr, $val, $blockhtml){
        if(strtolower($val) == 'no')
        return '';

        $icon = $this->getIcon($attr, $val);
        $text = $this->getText($attr, $val);
        
        $tooltip = $this->prepareHtml($attr, $val, $blockhtml);
        $tooltiptext = '';
        if($tooltip !=''){
        $tooltiptext = '<button class="featureinfoicon" >        
        <span class="glyphicon glyphicon-info-sign ballicon"></span></button>
        <span class="tooltiptexts">'.$tooltip.'</span>';
        }
        $html ="<center>$icon<br/><span class='label'>$label $tooltiptext</span><br/>";
        $html .="<span class='' style='padding:3px 5px 2px;'></span></center>"; 
        return $html;        
    }
    public function getMultiselecthtml($label, $attr, $val, $blockhtml){       
        if($val == ''){ return ''; }
        $valarr = []; 
        if(!is_array($val)){$valarr[] = $val; }else{$valarr = $val;}
        $html ="<div class='hide' style='width:100%;height:10px;clear:both;'></div>
        <div class='hide feature-attr-item feature-attr-heading'><strong>$label:</strong></div>";
        $i = 1;
        foreach($valarr as $v){
            $icon = $this->getIcon($attr, $v);
            $text = $this->getText($attr, $v);
            $tooltip = $this->prepareHtml($attr, $v, $blockhtml);
            $tooltiptext = '';
            if($tooltip !=''){
            $tooltiptext = '<button class="featureinfoicon" >        
            <span class="glyphicon glyphicon-info-sign ballicon"></span></button>
            <span class="tooltiptexts">'.$tooltip.'</span>';
            }
            if(count($valarr) > 1){
                $html .="<div class='feature-attr-item col-lg-2 col-md-2 col-sm-4 col-xs-6'><center>$icon<br/><span class='label'>$label $i $tooltiptext</span><br/><span class='text'>$text</span></center></div>";
            }else{
                $html .="<div class='feature-attr-item col-lg-2 col-md-2 col-sm-4 col-xs-6'><center>$icon<br/><span class='label'>$label $tooltiptext</span><br/><span class='text'>$text</span></center></div>";
            }
            
            $i++;
        }
        return $html;        
    } 
}