<?php

namespace ArtM\MsiPriority\Model\ResourceModel\VinRequest;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \ArtM\MsiPriority\Model\VinRequest::class,
            \ArtM\MsiPriority\Model\ResourceModel\VinRequest::class
        );
    }
}
