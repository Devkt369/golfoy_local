<?php
namespace ArtM\MassProducts\Model;

use Magento\Framework\Model\AbstractModel;

class Draft extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\MassProducts\Model\ResourceModel\Draft::class);
    }
}
