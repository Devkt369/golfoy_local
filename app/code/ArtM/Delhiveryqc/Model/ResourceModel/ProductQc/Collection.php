<?php
namespace ArtM\Delhiveryqc\Model\ResourceModel\ProductQc;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\Delhiveryqc\Model\ProductQc', 'ArtM\Delhiveryqc\Model\ResourceModel\ProductQc');
    }
}