<?php
namespace ArtM\PriceCompare\Model\ResourceModel\Product;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('ArtM\PriceCompare\Model\Product', 'ArtM\PriceCompare\Model\ResourceModel\Product');
    }
}
