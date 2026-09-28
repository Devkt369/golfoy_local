<?php

namespace ArtM\SSO\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    public function execute()
    {
        // Define the route name and controller path for the system configuration page
        $route = 'admin/system_config/edit';
        $params = ['section' => 'custom'];

        // Create a redirect result and set the URL
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_url->getUrl($route, $params));

        return $resultRedirect;
    }
}
