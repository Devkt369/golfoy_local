<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposMobile\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Redirect to webpos mobile checkout
 */
class Mobile extends Action implements HttpGetActionInterface
{
    const URL_TYPE_PWA_MOBILE = 'apps/mobile_pos';

    /**
     * Execute
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $url = $this->getPosMobileUrl() . '/';
        if ($this->isNginxServer()) {
            $url = $url . 'index.html';
        }
        return $this->_redirect($url);
    }

    /**
     * Get pos mobile url
     *
     * @return bool|string
     */
    public function getPosMobileUrl()
    {
        $filesystem = $this->_objectManager->create(Filesystem::class);
        /* @var StoreManagerInterface $storeManager */
        $storeManager = $this->_objectManager->get(
            StoreManagerInterface::class
        );
        $mediaUrl = $storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB)
            . $filesystem->getUri(DirectoryList::MEDIA);
        if (substr($mediaUrl, -strlen(UrlInterface::URL_TYPE_MEDIA . '/'))
            == UrlInterface::URL_TYPE_MEDIA . '/') {
            $parentUrl = substr(
                $mediaUrl,
                0,
                -strlen(UrlInterface::URL_TYPE_MEDIA . '/')
            );
        }
        if (substr($mediaUrl, -strlen(UrlInterface::URL_TYPE_MEDIA))
            == UrlInterface::URL_TYPE_MEDIA) {
            $parentUrl = substr($mediaUrl, 0, -strlen(UrlInterface::URL_TYPE_MEDIA));
        }
        $url = $parentUrl . self::URL_TYPE_PWA_MOBILE;
        return $url;
    }

    /**
     * Is nginx server
     *
     * @return bool
     */
    protected function isNginxServer()
    {
        $serverSoftware = $this->getRequest()->getServer('SERVER_SOFTWARE');
        $serverSoftware = strtolower($serverSoftware);
        return strpos($serverSoftware, 'nginx') !== false;
    }

    /**
     * Is allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magestore_WebposMobile::mobile_checkout');
    }
}
