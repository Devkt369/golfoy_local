<?php

namespace ArtM\ReservedProducts\Model;

class ReservedProducts extends \Magento\Framework\Model\AbstractModel
{
    public function __construct()
    {
        $this->_init('ArtM\ReservedProducts\Model\ResourceModel\ReservedProducts');
    }
}
