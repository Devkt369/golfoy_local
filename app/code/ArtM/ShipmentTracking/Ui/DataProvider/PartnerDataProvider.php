<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\DataProvider;

use ArtM\ShipmentTracking\Model\ResourceModel\Partner\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class PartnerDataProvider extends AbstractDataProvider
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

        $this->loadedData = [];
        foreach ($this->collection->getItems() as $partner) {
            $this->loadedData[$partner->getId()] = $partner->getData();
        }

        return $this->loadedData;
    }
}
