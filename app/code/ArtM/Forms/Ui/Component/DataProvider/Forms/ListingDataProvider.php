<?php
namespace ArtM\Forms\Ui\Component\DataProvider\Forms;

use Magento\Ui\DataProvider\AbstractDataProvider;
use ArtM\Forms\Model\ResourceModel\Forms\CollectionFactory;

class ListingDataProvider extends AbstractDataProvider
{
    protected $collection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        $items = $this->collection->getItems();
        return [
            'totalRecords' => $this->collection->getSize(),
            'items' => array_values(array_map(function ($item) {
                return $item->getData();
            }, $items)),
        ];
    }
}
