<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use ArtM\ProductSlider\Model\ResourceModel\SliderGroup\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class SliderGroups implements OptionSourceInterface
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [
            ['value' => '', 'label' => __('-- Please Select --')],
        ];

        $collection = $this->collectionFactory->create();
        $collection->setOrder('name', 'ASC');

        foreach ($collection as $item) {
            $options[] = [
                'value' => (int)$item->getId(),
                'label' => (string)$item->getData('name'),
            ];
        }

        return $options;
    }
}
