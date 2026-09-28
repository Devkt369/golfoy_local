<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\Partner;

use ArtM\ShipmentTracking\Model\PartnerFactory;
use ArtM\ShipmentTracking\Model\ResourceModel\Partner as PartnerResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::partner_management';

    public function __construct(
        Context $context,
        private readonly PartnerFactory $partnerFactory,
        private readonly PartnerResource $partnerResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $partnerId = (int)$this->getRequest()->getParam('partner_id');

        if (!$partnerId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a partner to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $partner = $this->partnerFactory->create();
            $this->partnerResource->load($partner, $partnerId);
            $this->partnerResource->delete($partner);
            $this->messageManager->addSuccessMessage(__('The partner has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
