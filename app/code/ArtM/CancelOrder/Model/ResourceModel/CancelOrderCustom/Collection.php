<?php
namespace ArtM\CancelOrder\Model\ResourceModel\CancelOrderCustom;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\CancelOrder\Model\CancelOrderCustom', 'ArtM\CancelOrder\Model\ResourceModel\CancelOrderCustom');
    }
}