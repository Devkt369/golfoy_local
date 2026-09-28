<?php
namespace ArtM\Common\Plugin\Sitemap;

use Magento\Sitemap\Model\ResourceModel\Catalog\Category as MCategory;

class Category
{
    public function afterGetCollection(MCategory $subject, $result, $storeId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig   = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
        $strIds        = $scopeConfig->getValue('layout/general/category_no_index',
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
