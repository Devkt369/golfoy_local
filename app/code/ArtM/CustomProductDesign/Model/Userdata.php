<?php
namespace ArtM\CustomProductDesign\Model;

use Magento\Framework\Model\AbstractModel;

class Userdata extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('ArtM\CustomProductDesign\Model\ResourceModel\Userdata');
    }
}
