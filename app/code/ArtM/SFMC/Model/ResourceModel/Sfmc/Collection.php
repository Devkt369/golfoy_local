<?php
namespace ArtM\SFMC\Model\ResourceModel\Sfmc;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\Sfmc', 'ArtM\SFMC\Model\ResourceModel\Sfmc');
    }
}