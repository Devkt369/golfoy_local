<?php 
namespace ArtM\Celebration\Model\ResourceModel\Celebration;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\Celebration\Model\Celebration',
            'ArtM\Celebration\Model\ResourceModel\Celebration'
	    );
    }
}