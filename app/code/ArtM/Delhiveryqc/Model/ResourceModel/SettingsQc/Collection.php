<?php
namespace ArtM\Delhiveryqc\Model\ResourceModel\SettingsQc;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\Delhiveryqc\Model\SettingsQc', 'ArtM\Delhiveryqc\Model\ResourceModel\SettingsQc');
    }
}