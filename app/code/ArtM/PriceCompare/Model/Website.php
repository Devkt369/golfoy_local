<?php
namespace ArtM\PriceCompare\Model;

use Magento\Framework\Model\AbstractModel;

class Website extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('ArtM\PriceCompare\Model\ResourceModel\Website');
    }
}
