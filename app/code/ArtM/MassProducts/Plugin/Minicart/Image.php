<?php

namespace ArtM\MassProducts\Plugin\Minicart;

class Image
{

    public function aroundGetItemData($subject, $proceed, $item)
    {

        $result = $proceed($item);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($result['product_id']);

        /* thum url */ 
        $storeManager = $objectManager->create('Magento\Store\Model\StoreManagerInterface'); 
        $currentStore = $storeManager->getStore();
        $mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        if($product->getThumbnail()){
            $result['product_image']['src'] = $mediaUrl.$product->getThumbnail();
        }
        else{
            $result['product_image']['src'];
        }
        return $result;
    }
}