<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Ui\DataProvider\SliderGroup;

use ArtM\ProductSlider\Model\ResourceModel\SliderGroup\Grid\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ListingDataProvider extends AbstractDataProvider
{
	public function __construct(
		string $name,
		string $primaryFieldName,
		string $requestFieldName,
		CollectionFactory $collectionFactory,
		array $meta = [],
		array $data = []
	) {
		$this->collection = $collectionFactory->create();
		parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
	}

	public function getData(): array
	{
		$items = [];
		foreach ($this->getCollection()->getItems() as $item) {
			$items[] = $item->getData();
		}

		return [
			'totalRecords' => $this->getCollection()->getSize(),
			'items' => $items,
		];
	}
}
