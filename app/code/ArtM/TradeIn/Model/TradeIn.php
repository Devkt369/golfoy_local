<?php

namespace ArtM\TradeIn\Model;

use Magento\Framework\Model\AbstractModel;

class TradeIn extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\TradeIn\Model\ResourceModel\TradeIn::class);
    }
}
