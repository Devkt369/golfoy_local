<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\ResourceModel\SliderGroupItem;

use ArtM\ProductSlider\Model\ResourceModel\SliderGroupItem as SliderGroupItemResource;
use ArtM\ProductSlider\Model\SliderGroupItem as SliderGroupItemModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(SliderGroupItemModel::class, SliderGroupItemResource::class);
    }
}
