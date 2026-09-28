<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcOrderSyncSucceed;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcOrderSyncSucceed', 'ArtM\SFMC\Model\ResourceModel\SfmcOrderSyncSucceed');
    }
}