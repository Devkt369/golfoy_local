<?php
namespace ArtM\PriceCompare\Model\ResourceModel\Website;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('ArtM\PriceCompare\Model\Website', 'ArtM\PriceCompare\Model\ResourceModel\Website');
    }
}
