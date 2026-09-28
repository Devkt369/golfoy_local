<?php

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\ImageUploader;
use ArtM\CustomerSurvey\Model\ResourceModel\Location\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class LocationOptions implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ImageUploader
     */
    private $imageUploader;

    public function __construct(
        CollectionFactory $collectionFactory,
        ImageUploader $imageUploader
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->imageUploader = $imageUploader;
    }

    public function toOptionArray()
    {
        $options = [];
        $collection = $this->collectionFactory->create();
        $collection->setOrder('name', 'ASC');

        foreach ($collection as $location) {
            $screenshot = (string)($location->getData('screenshot') ?? '');
            $options[] = [
                'value' => (string)$location->getId(),
                'label' => sprintf('#%d %s', (int)$location->getId(), (string)$location->getData('name')),
                'location_id' => (string)$location->getId(),
                'name' => (string)$location->getData('name'),
                'page_type' => (string)$location->getData('page_type'),
                'screenshot' => $screenshot,
                'screenshot_url' => $screenshot !== '' ? $this->imageUploader->getMediaUrl($screenshot) : '',
            ];
        }

        return $options;
    }
}
