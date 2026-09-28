<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\Partner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::partner_management';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_ShipmentTracking::partner_management');
        $resultPage->getConfig()->getTitle()->prepend(__('Partner Management'));

        return $resultPage;
    }
}
