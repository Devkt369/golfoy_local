<?php
namespace ArtM\MassProducts\Model;

use Magento\Framework\Model\AbstractModel;

class Mapping extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\MassProducts\Model\ResourceModel\Mapping::class);
    }
}
