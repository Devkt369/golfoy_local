<?php
namespace ArtM\BlogEsg\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $customerRepository;
    protected $_storeManager;
    protected $_getentries;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \ArtM\BlogEsg\Model\GetentriesFactory $_getentries,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_storeManager = $storeManager;
        $this->_getentries = $_getentries;
    }
    
    
    public function canshowblog(){
        return true;
    }
    public function getrow($attributesetname){
        if( $this->canshowblog() ){
            $getentries_model = $this->_getentries->create();
            $getentries_collection = $getentries_model->getCollection()->addFieldToFilter('attributesetname', $attributesetname);
            $blogdata = array();
            foreach($getentries_collection as $item){
                $blogdata[] = $item->getData(); 
            }   
            return $blogdata;
        }
        return false;
    }
    
    
}