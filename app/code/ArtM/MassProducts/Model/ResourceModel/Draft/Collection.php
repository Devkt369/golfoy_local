<?php
namespace ArtM\MassProducts\Model\ResourceModel\Draft;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\MassProducts\Model\Draft::class,
            \ArtM\MassProducts\Model\ResourceModel\Draft::class
        );
    }
}
