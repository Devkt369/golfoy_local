<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\TrackingStatus;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\ShipmentTracking\Model\TrackingStatusMappingFactory;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping as MappingResource;
use ArtM\ShipmentTracking\Model\TrackingStatusMappingRepository;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::tracking_status';

    public function __construct(
        Context $context,
        private readonly TrackingStatusMappingFactory    $mappingFactory,
        private readonly MappingResource                 $mappingResource,
        private readonly TrackingStatusMappingRepository $mappingRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $mappingId      = (int)$this->getRequest()->getParam('mapping_id');

        if (!$mappingId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a mapping to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model = $this->mappingFactory->create();
            $this->mappingResource->load($model, $mappingId);
            $this->mappingResource->delete($model);
            $this->mappingRepository->flushCache();
            $this->messageManager->addSuccessMessage(__('The mapping has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
