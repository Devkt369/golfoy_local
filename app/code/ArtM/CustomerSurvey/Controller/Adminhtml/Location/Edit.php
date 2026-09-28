<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Location;

use ArtM\CustomerSurvey\Api\LocationRepositoryInterface;
use ArtM\CustomerSurvey\Model\LocationFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::locations';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var LocationFactory
     */
    private $locationFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        LocationRepositoryInterface $locationRepository,
        LocationFactory $locationFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->locationRepository = $locationRepository;
        $this->locationFactory = $locationFactory;
    }

    public function execute()
    {
        $locationId = (int)$this->getRequest()->getParam('location_id');

        try {
            $location = $locationId
                ? $this->locationRepository->getById($locationId)
                : $this->locationFactory->create();
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('This location no longer exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $this->registry->register('artm_customersurvey_location', $location);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_CustomerSurvey::locations');
        $resultPage->getConfig()->getTitle()->prepend($locationId ? __('Edit Location') : __('New Location'));

        return $resultPage;
    }
}
