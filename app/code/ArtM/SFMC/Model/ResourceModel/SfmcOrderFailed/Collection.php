<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcOrderFailed;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcOrderFailed', 'ArtM\SFMC\Model\ResourceModel\SfmcOrderFailed');
    }
}