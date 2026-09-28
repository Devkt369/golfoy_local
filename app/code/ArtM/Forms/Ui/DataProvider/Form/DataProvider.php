<?php
namespace ArtM\Forms\Ui\DataProvider\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use ArtM\Forms\Model\ResourceModel\Forms\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    protected $loadedData;

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
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

            $data = [];
            foreach ($this->collection->getItems() as $item) {
                $row = $item->getData();
                if (isset($row['form_fields']) && $row['form_fields']) {
                    $row['form_fields'] = json_decode($row['form_fields'], true);
                }
                $data[$item->getId()] = $row;
            }
            return $data;
    // removed erroneous extra return
    }
}
