<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping\CollectionFactory;

class TrackingStatusMappingDataProvider extends AbstractDataProvider
{
    protected $_loadedData;
    protected $collection;

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
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }

        $items = [];
        foreach ($this->collection->getItems() as $mapping) {
            $items[] = $mapping->getData();
        }

        $this->_loadedData = [
            'totalRecords' => $this->collection->getSize(),
            'items'        => $items,
        ];

        return $this->_loadedData;
    }
}
