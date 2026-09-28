<?php

namespace ArtM\BrandInventory\Model;

use Magento\Framework\Model\AbstractModel;

class BrandInventory extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\BrandInventory\Model\ResourceModel\BrandInventory::class);
    }
}