<?php
namespace ArtM\BlogFaq\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $customerRepository;
    protected $_storeManager;
    protected $_getfaq;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \ArtM\BlogFaq\Model\GetfaqFactory $_getfaq,
        \Magento\Store\Model\StoreManagerInterface $_storeManager
    )
    {
        $this->_storeManager = $_storeManager;
        $this->_getfaq = $_getfaq;
    }
    
    
    public function canshowblog(){
        return true;
    }
    public function getrow($attributesetname){
        if( $this->canshowblog() ){
            $getfaq_model = $this->_getfaq->create();
            $getfaq_collection = $getfaq_model->getCollection()->addFieldToFilter('attributesetname', $attributesetname);
            $blogdata = array();
            foreach($getfaq_collection as $item){
                $blogdata[] = $item->getData(); 
            }   
            return $blogdata;
        }
        return false;
    }
    
    
}