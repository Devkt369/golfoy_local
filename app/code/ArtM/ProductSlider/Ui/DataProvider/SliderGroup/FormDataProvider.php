<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Ui\DataProvider\SliderGroup;

use ArtM\ProductSlider\Model\ResourceModel\SliderGroup\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class FormDataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== []) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $item) {
            $this->loadedData[(int)$item->getId()] = $item->getData();
        }

        $persistedData = $this->dataPersistor->get('artm_productslider_group');
        if (!empty($persistedData)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($persistedData);
            $this->loadedData[(int)$item->getId()] = $item->getData();
            $this->dataPersistor->clear('artm_productslider_group');
        }

        return $this->loadedData;
    }
}
