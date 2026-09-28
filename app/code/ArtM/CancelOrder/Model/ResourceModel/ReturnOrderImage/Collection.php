<?php
namespace ArtM\CancelOrder\Model\ResourceModel\ReturnOrderImage;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\CancelOrder\Model\ReturnOrderImage', 'ArtM\CancelOrder\Model\ResourceModel\ReturnOrderImage');
    }
}