<?php
namespace ArtM\CustomPrinting\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{    
    protected $_getentries;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \ArtM\CustomPrinting\Model\CustomprintingFactory $_getentries
    )
    {
        $this->_getentries = $_getentries;
    }
    public function getrow($attributeset){
            
            $getentries_model = $this->_getentries->create(); 
            $getentries_collection = $getentries_model->getCollection()->addFieldToFilter('attributeset', $attributeset);

           $data = [];
            foreach($getentries_collection as $item){
                $data[] = $item->getData(); 
            } 
           
            return $data;
    }
    
    
}