<?php

namespace ArtM\TradeIn\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TradeIn extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_tradein', 'tradein_id');
    }
}
