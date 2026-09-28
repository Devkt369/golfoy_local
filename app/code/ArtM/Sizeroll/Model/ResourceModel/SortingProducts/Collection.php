<?php
namespace ArtM\Sizeroll\Model\ResourceModel\SortingProducts;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\Sizeroll\Model\SortingProducts::class,
            \ArtM\Sizeroll\Model\ResourceModel\SortingProducts::class
        );
    }
}
