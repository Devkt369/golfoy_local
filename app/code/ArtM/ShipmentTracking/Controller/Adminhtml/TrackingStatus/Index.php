<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\TrackingStatus;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::tracking_status';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_ShipmentTracking::tracking_status');
        $resultPage->getConfig()->getTitle()->prepend(__('Tracking Status Mappings'));
        return $resultPage;
    }
}
