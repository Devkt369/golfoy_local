<?php 
namespace ArtM\BlogEsg\Model\ResourceModel\Getentries;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
           \ArtM\BlogEsg\Model\Getentries::class,
            \ArtM\BlogEsg\Model\ResourceModel\Getentries::class
	    );
    }
}
