<?php 
namespace ArtM\Common\Model\ResourceModel\Customcache;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \ArtM\Common\Model\Customcache::class,
            \ArtM\Common\Model\ResourceModel\Customcache::class
	    );
    }
}