<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\TrackingStatus;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\ShipmentTracking\Model\TrackingStatusMappingFactory;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping as MappingResource;
use ArtM\ShipmentTracking\Model\TrackingStatusMappingRepository;
use ArtM\ShipmentTracking\Model\Service\TrackingStatusLogger;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::tracking_status';

    public function __construct(
        Context $context,
        private readonly TrackingStatusMappingFactory    $mappingFactory,
        private readonly MappingResource                 $mappingResource,
        private readonly TrackingStatusMappingRepository $mappingRepository,
        private readonly TrackingStatusLogger            $statusLogger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data           = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $mappingId = (int)($data['mapping_id'] ?? 0);

        try {
            $model = $this->mappingFactory->create();

            if ($mappingId) {
                $this->mappingResource->load($model, $mappingId);
                if (!$model->getMappingId()) {
                    $this->messageManager->addErrorMessage(__('This mapping no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            // Normalize raw_status before saving
            $data['raw_status'] = strtolower(trim((string)($data['raw_status'] ?? '')));
            $data['raw_status'] = (string)preg_replace('/\s+/', ' ', str_replace('_', ' ', $data['raw_status']));

            $model->setData($data);

            // Sync is_mapped in the log table as well
            if ((int)($data['is_mapped'] ?? 0) === 1) {
                $this->statusLogger->markMapped(
                    $data['raw_status'],
                    strtolower(trim((string)($data['carrier_name'] ?? '')))
                );
            }

            $this->mappingResource->save($model);
            $this->mappingRepository->flushCache();

            $this->messageManager->addSuccessMessage(__('The mapping has been saved.'));

        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the mapping.'));
            return $resultRedirect->setPath('*/*/edit', ['mapping_id' => $mappingId ?: null]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
