<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Controller\Adminhtml\TrackingStatus;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\ShipmentTracking\Model\TrackingStatusMappingFactory;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping as MappingResource;
use ArtM\ShipmentTracking\Model\TrackingStatusMappingRepository;
use ArtM\ShipmentTracking\Model\Service\TrackingStatusLogger;

class InlineEdit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ShipmentTracking::tracking_status';

    public function __construct(
        Context $context,
        private readonly JsonFactory                    $jsonFactory,
        private readonly TrackingStatusMappingFactory  $mappingFactory,
        private readonly MappingResource               $mappingResource,
        private readonly TrackingStatusMappingRepository $mappingRepository,
        private readonly TrackingStatusLogger          $statusLogger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error      = false;
        $messages   = [];

        $postItems = $this->getRequest()->getParam('items', []);

        // echo "<pre>";
        // print_r($this->getRequest()->getParams());die;

        if (!$this->getRequest()->getParam('isAjax') || empty($postItems)) {
            return $resultJson->setData(['messages' => [__('Please correct the sent data.')], 'error' => true]);
        }

        foreach ($postItems as $mappingId => $mappingData) {
            try {
                /** @var \ArtM\ShipmentTracking\Model\TrackingStatusMapping $model */
                $model = $this->mappingFactory->create();
                $this->mappingResource->load($model, $mappingId);

                if (!$model->getMappingId()) {
                    $messages[] = __('Mapping with ID %1 does not exist.', $mappingId);
                    $error = true;
                    continue;
                }

                // Allowed fields for inline edit
                $allowed = ['display_label', 'carrier_name', 'is_active', 'is_mapped'];
                foreach ($allowed as $field) {
                    if (array_key_exists($field, $mappingData)) {
                        $model->setData($field, $mappingData[$field]);
                    }
                }

                // When admin marks is_mapped = 1, also update the log table
                if (isset($mappingData['is_mapped']) && (int)$mappingData['is_mapped'] === 1) {
                    $model->setIsMapped(1);
                    $this->statusLogger->markMapped(
                        $model->getRawStatus(),
                        $model->getCarrierName()
                    );
                }

                $this->mappingResource->save($model);

            } catch (\Exception $e) {
                $messages[] = $e->getMessage();
                $error = true;
            }
        }

        // Flush mapping cache so next request picks up the new values
        $this->mappingRepository->flushCache();

        return $resultJson->setData(['messages' => $messages, 'error' => $error]);
    }
}
