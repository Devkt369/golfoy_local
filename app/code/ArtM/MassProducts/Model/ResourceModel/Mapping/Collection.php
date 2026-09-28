<?php
namespace ArtM\MassProducts\Model\ResourceModel\Mapping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\ArtM\MassProducts\Model\Mapping::class, \ArtM\MassProducts\Model\ResourceModel\Mapping::class);
    }
}
