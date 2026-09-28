<?php
namespace ArtM\BulkOrder\Model\ResourceModel\Bulkorder;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\BulkOrder\Model\Bulkorder', 'ArtM\BulkOrder\Model\ResourceModel\Bulkorder');
    }
}