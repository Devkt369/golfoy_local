<?php

namespace ArtM\BrandInventory\Model\ResourceModel\BrandInventory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\BrandInventory\Model\BrandInventory::class,
            \ArtM\BrandInventory\Model\ResourceModel\BrandInventory::class
        );
    }
}