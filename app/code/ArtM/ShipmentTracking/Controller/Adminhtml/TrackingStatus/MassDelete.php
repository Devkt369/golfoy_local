<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\TrackingStatus;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping\CollectionFactory;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping as MappingResource;
use ArtM\ShipmentTracking\Model\TrackingStatusMappingRepository;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::tracking_status';

    public function __construct(
        Context $context,
        private readonly Filter                          $filter,
        private readonly CollectionFactory               $collectionFactory,
        private readonly MappingResource                 $mappingResource,
        private readonly TrackingStatusMappingRepository $mappingRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted    = 0;

        foreach ($collection as $item) {
            try {
                $this->mappingResource->delete($item);
                $deleted++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        $this->mappingRepository->flushCache();
        $this->messageManager->addSuccessMessage(__('A total of %1 mapping(s) have been deleted.', $deleted));

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
