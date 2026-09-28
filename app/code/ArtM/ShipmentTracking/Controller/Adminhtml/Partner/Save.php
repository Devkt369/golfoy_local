<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\Partner;

use ArtM\ShipmentTracking\Model\PartnerFactory;
use ArtM\ShipmentTracking\Model\ResourceModel\Partner as PartnerResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Save extends Action
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
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $partnerId = (int)($data['partner_id'] ?? $this->getRequest()->getParam('partner_id', 0));
        if ($partnerId) {
            $data['partner_id'] = $partnerId;
        }

        try {
            $partner = $this->partnerFactory->create();
            if ($partnerId) {
                $this->partnerResource->load($partner, $partnerId);
                if (!$partner->getId()) {
                    $this->messageManager->addErrorMessage(__('This partner no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            $partner->setData($this->prepareData($data));
            $this->partnerResource->save($partner);
            $this->messageManager->addSuccessMessage(__('The partner has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['partner_id' => $partner->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the partner.'));
            return $resultRedirect->setPath('*/*/edit', ['partner_id' => $partnerId ?: null]);
        }
    }

    private function prepareData(array $data): array
    {
        $prepared = [
            'partner_name' => trim((string)($data['partner_name'] ?? '')),
            'is_active' => (int)($data['is_active'] ?? 0),
            'api_url' => trim((string)($data['api_url'] ?? '')),
            'username' => trim((string)($data['username'] ?? '')),
            'auth_type' => trim((string)($data['auth_type'] ?? '')),
            'token' => trim((string)($data['token'] ?? '')),
            'last_sync_at' => trim((string)($data['last_sync_at'] ?? '')),
        ];

        if (!empty($data['partner_id'])) {
            $prepared['partner_id'] = (int)$data['partner_id'];
        }

        if ($prepared['last_sync_at'] === '') {
            $prepared['last_sync_at'] = null;
        }

        return $prepared;
    }
}
