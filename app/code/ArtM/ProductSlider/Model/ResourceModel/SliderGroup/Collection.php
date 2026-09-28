<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\ResourceModel\SliderGroup;

use ArtM\ProductSlider\Model\ResourceModel\SliderGroup as SliderGroupResource;
use ArtM\ProductSlider\Model\SliderGroup as SliderGroupModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(SliderGroupModel::class, SliderGroupResource::class);
    }
}
