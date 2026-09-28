<?php

namespace ArtM\ReservedProducts\Model\ResourceModel\ReservedProducts;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\ReservedProducts\Model\ReservedProducts',
            'ArtM\ReservedProducts\Model\ResourceModel\ReservedProducts'
        );
    }
}
