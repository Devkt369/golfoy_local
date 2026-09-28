<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\ShipmentLogs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::shipment_logs';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_ShipmentTracking::shipment_logs');
        $resultPage->getConfig()->getTitle()->prepend(__('Order Marked Delivered Logs'));

        return $resultPage;
    }
}
