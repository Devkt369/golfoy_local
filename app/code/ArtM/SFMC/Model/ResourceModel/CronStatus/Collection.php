<?php

namespace ArtM\SFMC\Model\ResourceModel\CronStatus;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\CronStatus', 'ArtM\SFMC\Model\ResourceModel\CronStatus');
    }
}
