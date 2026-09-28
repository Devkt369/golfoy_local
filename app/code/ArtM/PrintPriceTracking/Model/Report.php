<?php

namespace ArtM\PrintPriceTracking\Model;

use Magento\Framework\Model\AbstractModel;

class Report extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\PrintPriceTracking\Model\ResourceModel\Report::class);
    }
}