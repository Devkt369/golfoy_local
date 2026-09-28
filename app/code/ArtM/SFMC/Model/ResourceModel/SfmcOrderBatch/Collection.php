<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcOrderBatch;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcOrderBatch', 'ArtM\SFMC\Model\ResourceModel\SfmcOrderBatch');
    }
}