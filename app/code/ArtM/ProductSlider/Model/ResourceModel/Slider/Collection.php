<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\ResourceModel\Slider;

use ArtM\ProductSlider\Model\ResourceModel\Slider as SliderResource;
use ArtM\ProductSlider\Model\Slider as SliderModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(SliderModel::class, SliderResource::class);
    }
}
