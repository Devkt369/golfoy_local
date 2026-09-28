<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\Partner;

use ArtM\ShipmentTracking\Model\PartnerFactory;
use ArtM\ShipmentTracking\Model\ResourceModel\Partner as PartnerResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class InlineEdit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::partner_management';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly PartnerFactory $partnerFactory,
        private readonly PartnerResource $partnerResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!$this->getRequest()->getParam('isAjax') || empty($postItems)) {
            return $resultJson->setData(['messages' => [__('Please correct the sent data.')], 'error' => true]);
        }

        foreach ($postItems as $partnerId => $partnerData) {
            try {
                $partner = $this->partnerFactory->create();
                $this->partnerResource->load($partner, $partnerId);

                if (!$partner->getId()) {
                    $messages[] = __('Partner with ID %1 does not exist.', $partnerId);
                    $error = true;
                    continue;
                }

                $allowed = ['partner_name', 'is_active', 'api_url', 'username', 'auth_type', 'token', 'last_sync_at'];
                foreach ($allowed as $field) {
                    if (array_key_exists($field, $partnerData)) {
                        $partner->setData($field, $partnerData[$field] === '' ? null : $partnerData[$field]);
                    }
                }

                $this->partnerResource->save($partner);
            } catch (\Exception $e) {
                $messages[] = $e->getMessage();
                $error = true;
            }
        }

        return $resultJson->setData(['messages' => $messages, 'error' => $error]);
    }
}
