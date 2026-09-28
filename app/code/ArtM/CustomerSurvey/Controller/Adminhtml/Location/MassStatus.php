<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Location;

use ArtM\CustomerSurvey\Api\LocationRepositoryInterface;
use ArtM\CustomerSurvey\Model\ResourceModel\Location\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;

class MassStatus extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::location_mass';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        LocationRepositoryInterface $locationRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->locationRepository = $locationRepository;
    }

    public function execute()
    {
        $status = (int)$this->getRequest()->getParam('status', 1);
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $updated = 0;
        foreach ($collection as $location) {
            $location->setStatus($status);
            $this->locationRepository->save($location);
            $updated++;
        }

        $this->messageManager->addSuccessMessage(__('%1 location(s) updated.', $updated));

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
