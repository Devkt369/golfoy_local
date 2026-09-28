<?php 
namespace ArtM\CustomPrinting\Model\ResourceModel\Customprinting;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\CustomPrinting\Model\Customprinting::class,
            \ArtM\CustomPrinting\Model\ResourceModel\Customprinting::class
	    );
    }
}
