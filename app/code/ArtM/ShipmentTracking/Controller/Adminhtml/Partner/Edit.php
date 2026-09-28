<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\Partner;

use ArtM\ShipmentTracking\Model\PartnerFactory;
use ArtM\ShipmentTracking\Model\ResourceModel\Partner as PartnerResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::partner_management';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly PartnerFactory $partnerFactory,
        private readonly PartnerResource $partnerResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $partnerId = (int)$this->getRequest()->getParam('partner_id');
        $partner = $this->partnerFactory->create();

        if ($partnerId) {
            $this->partnerResource->load($partner, $partnerId);
            if (!$partner->getId()) {
                $this->messageManager->addErrorMessage(__('This partner no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_ShipmentTracking::partner_management');
        $resultPage->getConfig()->getTitle()->prepend(
            $partnerId ? __('Edit Partner') : __('New Partner')
        );

        return $resultPage;
    }
}
