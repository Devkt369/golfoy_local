<?php

namespace ArtM\ProductFeatures\ViewModel;

class Feature implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    protected $attrFactory;
    protected $registry;
    protected $AttributeSetRepositoryInterface;
    protected $pblock;
    protected $eavConfig;
    public function __construct(
        \ArtM\ProductFeatures\Model\GetproductfeaturesFactory $GetproductfeaturesFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Block\Product\View $pblock,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $AttributeSetRepositoryInterface
    ) {
        $this->attrFactory = $GetproductfeaturesFactory;
        $this->registry = $registry;
        $this->pblock = $pblock;
        $this->eavConfig = $eavConfig;
        $this->AttributeSetRepositoryInterface = $AttributeSetRepositoryInterface;
    }
    public function getProduct()
    {
        $product =  $this->pblock->getProduct();        
        return $product;
    }
    public function getFeaturedAttr()
    {
        $product =  $this->getProduct();
        $attributeSetRepository = $this->AttributeSetRepositoryInterface->get($product->getAttributeSetId());
        $attributesetname = $attributeSetRepository->getAttributeSetName();
        return $this->getrow(strtolower($attributesetname));
    }
    public function getrow($attributesetname){
            $attrdata = '';
            $data = [];
            $attrrepo = $this->attrFactory->create();
            $collection = $attrrepo->getCollection()->addFieldToFilter('attributesetname', $attributesetname);
            foreach($collection as $item){
                $attrdata = $item->getAttributes(); 
            }
            if($attrdata != ''){
                $data = explode(',',$attrdata);
            }   
            return $data;      
    }  
    public function getAttrdata($attr){
        $attributeDetails = $this->eavConfig->getAttribute("catalog_product", $attr);       
       // echo "<pre>"; print_r($attributeDetails->getData());   echo "</pre>";
        $label = $attributeDetails->getData('frontend_label');
        $type =  $attributeDetails->getData('frontend_input'); 
        return ['label' => $label, 'type' => $type];
    }
    public function getAttroptions($attr){
        $attributeDetails = $this->eavConfig->getAttribute("catalog_product", $attr);
        $options = $attributeDetails->getSource()->getAllOptions();
        unset($options[0]);
        return $options; 
    }

}