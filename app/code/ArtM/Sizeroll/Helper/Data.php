<?php
namespace ArtM\Sizeroll\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{    
    protected $_getentries;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \ArtM\Sizeroll\Model\SizerollFactory $_getentries
    )
    {
        $this->_getentries = $_getentries;
    }
    public function getrow($attributeset, $brand = ''){
            
            $getentries_model = $this->_getentries->create();        
            if($brand != ''){
                $getentries_collection = $getentries_model->getCollection()->addFieldToFilter('brand', $brand)
                                         ->addFieldToFilter('status', 1)
                                         ->addFieldToFilter('attributeset', $attributeset);

                //echo $getentries_collection->getSize();

                if($getentries_collection->getSize() < 1){
                    $getentries_collection = $getentries_model->getCollection()->addFieldToFilter('attributeset', $attributeset)
                                            ->addFieldToFilter('brand', '')
                                            ->addFieldToFilter('status', 1);
                }

            }else{
                $getentries_collection = $getentries_model->getCollection()->addFieldToFilter('attributeset', $attributeset)
                                            ->addFieldToFilter('brand', '')
                                            ->addFieldToFilter('status', 1);
            }
           
          //  $getentries_collection = $getentries_model->getCollection();
          // print_r($getentries_collection->getData());  die; 
           $data = [];
            foreach($getentries_collection as $item){
                $data[] = $item->getData(); 
            } 
            return $data;
    }
    
    
}