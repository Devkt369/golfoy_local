<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Location;

use ArtM\CustomerSurvey\Api\LocationRepositoryInterface;
use Magento\Backend\App\Action;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::location_delete';

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    public function __construct(
        Action\Context $context,
        LocationRepositoryInterface $locationRepository
    ) {
        parent::__construct($context);
        $this->locationRepository = $locationRepository;
    }

    public function execute()
    {
        $locationId = (int)$this->getRequest()->getParam('location_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$locationId) {
            $this->messageManager->addErrorMessage(__('Location id is required.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $this->locationRepository->deleteById($locationId);
            $this->messageManager->addSuccessMessage(__('Location has been deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to delete location.'));
            return $resultRedirect->setPath('*/*/edit', ['location_id' => $locationId]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
