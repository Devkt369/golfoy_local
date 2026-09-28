<?php 
namespace ArtM\Sizeroll\Model\ResourceModel\Sizeroll;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
           \ArtM\Sizeroll\Model\Sizeroll::class,
            \ArtM\Sizeroll\Model\ResourceModel\Sizeroll::class
	    );
    }
}
