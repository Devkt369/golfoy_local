<?php
namespace ArtM\MsiPriority\Model\ResourceModel\VinRequest;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\MsiPriority\Model\VinRequest', 'ArtM\MsiPriority\Model\ResourceModel\VinRequest');
    }
}