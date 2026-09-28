<?php
namespace ArtM\Common\Plugin\Sitemap;

use Magento\Sitemap\Model\ResourceModel\Cms\Page as MPage;

class Page
{
    public function afterGetCollection(MPage $subject, $result, $storeId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig   = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
        $strIds        = $scopeConfig->getValue('layout/general/cms_no_index',
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $strIds =   trim((string) $strIds); 
        if($strIds != ''){
        $ids = explode(',', $strIds);
        foreach($ids as $id){
            unset($result[trim($id)]);
        }} 
        return $result;
    }
}
