<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Location;

use ArtM\CustomerSurvey\Api\LocationRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use ArtM\CustomerSurvey\Model\ResourceModel\Location\CollectionFactory;

class MassDelete extends Action
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
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted = 0;

        foreach ($collection as $location) {
            $this->locationRepository->delete($location);
            $deleted++;
        }

        $this->messageManager->addSuccessMessage(__('%1 location(s) deleted.', $deleted));

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
