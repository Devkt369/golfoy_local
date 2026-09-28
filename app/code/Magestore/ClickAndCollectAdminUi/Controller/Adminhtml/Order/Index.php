<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magestore\ClickAndCollectAdminUi\Helper\Data as HelperData;

/**
 * Redirect to click and collect app
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_ClickAndCollectAdminUi::manage_order';

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * Index constructor.
     * @param Context $context
     * @param HelperData $helperData
     */
    public function __construct(
        Context $context,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
    }

    /**
     * Execute
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $url = $this->helperData->getClickAndCollectAppUrl() . '/';
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
}
