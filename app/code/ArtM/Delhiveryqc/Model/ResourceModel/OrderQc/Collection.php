<?php
namespace ArtM\Delhiveryqc\Model\ResourceModel\OrderQc;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\Delhiveryqc\Model\OrderQc', 'ArtM\Delhiveryqc\Model\ResourceModel\OrderQc');
    }
}