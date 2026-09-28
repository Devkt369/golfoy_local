<?php
namespace ArtM\MsiPriority\Model\ResourceModel\ArchiveOrders;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\MsiPriority\Model\ArchiveOrders', 'ArtM\MsiPriority\Model\ResourceModel\ArchiveOrders');
    }
}