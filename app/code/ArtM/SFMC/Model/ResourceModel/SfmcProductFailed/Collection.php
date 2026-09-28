<?php
namespace ArtM\SFMC\Model\ResourceModel\SfmcProductFailed;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\SfmcProductFailed', 'ArtM\SFMC\Model\ResourceModel\SfmcProductFailed');
    }
}