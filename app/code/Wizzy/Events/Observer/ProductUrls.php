<?php

/**
Copyright © Magento, Inc. All rights reserved.
See COPYING.txt for license details.
 */

namespace Wizzy\Events\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Url as BackendUrl;

class ProductUrls implements ObserverInterface
{
    private $adminUrl = NULL;
    private $backendUrl;

    public function __construct(BackendUrl $backendUrl)
    {
        $this->backendUrl = $backendUrl;
    }

    public function execute(Observer $observer)
    {
        $data = $observer->getData('data');
        $product = $data->getDataByKey('mapped');
        $m2Product = $data->getDataByKey('product');
        $product['url'] = $this->cleanProductUrl($product, $m2Product);
        $data->setData('mapped', $product);
        return $data;
    }

    public function cleanProductUrl($product, $m2Product)
    {
        $this->setAdminUrl();
        $url = $product['url'];
        $urlParts = parse_url($url);
        if ($this->isAdminUrl($url)) {
            $urlParts['path'] = $m2Product->getData('url_key');
        }
        $routes = explode("/", $urlParts['path']);
        $routes = array_filter($routes);
        $routes = array_map(function($route){
            return urlencode($route);
        }, $routes);
        $cleanRoutes = implode('/', $routes);
        $urlParts['path'] = $cleanRoutes;
        $url = $urlParts['scheme']."://".$urlParts['host']."/".$urlParts['path']."/";
        return $url;
    }

    private function isAdminUrl($url) {
        return (strpos($url, $this->adminUrl) !== false);
    }

    private function setAdminUrl()
    {
        if ($this->adminUrl === NULL) {
            $routePath = 'product/edit';
            $this->adminUrl = $this->backendUrl->getUrl($routePath);
            $this->adminUrl = substr($this->adminUrl, 0, strpos($this->adminUrl, $routePath));
        }
    }
}
