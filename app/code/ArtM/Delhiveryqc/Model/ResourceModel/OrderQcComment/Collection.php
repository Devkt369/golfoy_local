<?php
namespace ArtM\Delhiveryqc\Model\ResourceModel\OrderQcComment;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\Delhiveryqc\Model\OrderQcComment', 'ArtM\Delhiveryqc\Model\ResourceModel\OrderQcComment');
    }
}