<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\ResourceModel\SliderProduct;

use ArtM\ProductSlider\Model\ResourceModel\SliderProduct as SliderProductResource;
use ArtM\ProductSlider\Model\SliderProduct as SliderProductModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(SliderProductModel::class, SliderProductResource::class);
    }
}
