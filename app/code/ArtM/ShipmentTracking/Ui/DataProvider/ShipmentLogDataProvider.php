<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\DataProvider;

use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusLog\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ShipmentLogDataProvider extends AbstractDataProvider
{
    protected $collection;

    private ?array $loadedData = null;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $items = [];
        foreach ($this->collection->getItems() as $track) {
            $items[] = $track->getData();
        }

        $this->loadedData = [
            'totalRecords' => $this->collection->getSize(),
            'items' => $items,
        ];

        return $this->loadedData;
    }
}
