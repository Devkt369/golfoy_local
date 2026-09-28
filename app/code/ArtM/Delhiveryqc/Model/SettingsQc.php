<?php 
namespace ArtM\Delhiveryqc\Model;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class SettingsQc extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\Delhiveryqc\Model\ResourceModel\SettingsQc');
    }
}