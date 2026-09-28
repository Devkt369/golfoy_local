<?php 
namespace ArtM\TradeIn\Model\ResourceModel\TradeIn;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\TradeIn\Model\TradeIn::class,
            \ArtM\TradeIn\Model\ResourceModel\TradeIn::class
	    );
    }
}