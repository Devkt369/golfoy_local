<?php 

namespace ArtM\SFMC\Model;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SfmcOrderFailed extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\SFMC\Model\ResourceModel\SfmcOrderFailed');
    }
}

