<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Location;

use ArtM\CustomerSurvey\Model\ImageUploader;
use ArtM\CustomerSurvey\Model\ResourceModel\Location\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class GetAll extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::location_view';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ImageUploader
     */
    private $imageUploader;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CollectionFactory $collectionFactory,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->collectionFactory = $collectionFactory;
        $this->imageUploader = $imageUploader;
    }

    public function execute()
    {
        $collection = $this->collectionFactory->create();
        $collection->setOrder('name', 'ASC');

        $locations = [];
        foreach ($collection as $location) {
            $screenshot = (string)($location->getData('screenshot') ?? '');
            $locations[] = [
                'location_id' => (int)$location->getId(),
                'name' => (string)$location->getData('name'),
                'page_type' => (string)$location->getData('page_type'),
                'screenshot' => $screenshot,
                'screenshot_url' => $screenshot !== '' ? $this->imageUploader->getMediaUrl($screenshot) : '',
            ];
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($locations);
    }
}
