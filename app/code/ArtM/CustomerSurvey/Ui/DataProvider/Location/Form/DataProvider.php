<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Ui\DataProvider\Location\Form;

use ArtM\CustomerSurvey\Model\ImageUploader;
use ArtM\CustomerSurvey\Model\ResourceModel\Location\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private $loadedData = [];

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var ImageUploader
     */
    private $imageUploader;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        ImageUploader $imageUploader,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->imageUploader = $imageUploader;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $location) {
            $row = $location->getData();

            if (!empty($row['screenshot'])) {
                $row['screenshot'] = [[
                    'name' => $row['screenshot'],
                    'url' => $this->imageUploader->getMediaUrl((string) $row['screenshot']),
                ]];
            }

            $this->loadedData[(int) $location->getId()] = $row;
        }

        $persistedData = $this->dataPersistor->get('artm_customersurvey_location');
        if (!empty($persistedData)) {
            $location = $this->collection->getNewEmptyItem();
            $location->setData($persistedData);
            $this->loadedData[(int) $location->getId()] = $location->getData();
            $this->dataPersistor->clear('artm_customersurvey_location');
        }

        return $this->loadedData;
    }
}
