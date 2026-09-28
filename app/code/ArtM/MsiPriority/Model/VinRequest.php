<?php

namespace ArtM\MsiPriority\Model;

use Magento\Framework\Model\AbstractModel;

class VinRequest extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\MsiPriority\Model\ResourceModel\VinRequest::class);
    }
}
