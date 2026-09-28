<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Redirect to webpos checkout
 */
class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * Execute
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helperData = $objectManager->get(\Magestore\Webpos\Helper\Data::class);
        $url = $helperData->getPosUrl() . '/';
        if ($this->isNginxServer()) {
            $url = $url . 'index.html';
        }
        return $this->_redirect($url);
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
        return $this->_authorization->isAllowed('Magestore_Webpos::checkout');
    }
}
