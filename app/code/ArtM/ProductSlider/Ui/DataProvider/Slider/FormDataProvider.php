<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Ui\DataProvider\Slider;

use ArtM\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;
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
            $data = $item->getData();
            $data['slides_to_show'] = $this->extractSlidesToShowValues($data['slides_to_show'] ?? '');
            $this->loadedData[(int)$item->getId()] = $data;
        }

        $persistedData = $this->dataPersistor->get('artm_productslider_slider');
        if (!empty($persistedData)) {
            $persistedData['slides_to_show'] = $this->extractSlidesToShowValues($persistedData['slides_to_show'] ?? '');
            $item = $this->collection->getNewEmptyItem();
            $item->setData($persistedData);
            $this->loadedData[(int)$item->getId()] = $item->getData();
            $this->dataPersistor->clear('artm_productslider_slider');
        }

        return $this->loadedData;
    }

    private function extractSlidesToShowValues(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value), static fn(string $item): bool => $item !== ''));
        }

        if ($value === null || $value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', (string)$value)), static fn(string $item): bool => $item !== ''));
    }
}
