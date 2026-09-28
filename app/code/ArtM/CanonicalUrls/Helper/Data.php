<?php
namespace ArtM\CanonicalUrls\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{   
    private $storeManager;
    private $canonicalModel;
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,        
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \ArtM\CanonicalUrls\Model\GetcanonicalurlsFactory $GetcanonicalurlsFactory

    )
    {
        $this->storeManager = $storeManager; 
        $this->canonicalModel = $GetcanonicalurlsFactory;
    }
    public function getrows(){
        $data = [];
        $repo = $this->canonicalModel->create();
        $collection = $repo->getCollection();
        foreach($collection as $item){
            $data[intval($item->getCategoryid())] =$item->getUrl(); 
        }       
        return $data;      
    }  
    
    public function getPageRow($pageId)
    {
        $repo = $this->canonicalModel->create();

        $collection = $repo->getCollection()
            ->addFieldToFilter('page_id', (int)$pageId)
            ->setPageSize(1);

        $item = $collection->getFirstItem();

        if ($item && $item->getId()) {
            return $item->getUrl();
        }

        return false;
    }

}