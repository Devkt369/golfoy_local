<?php 

namespace ArtM\Review\Model\ResourceModel\Getreviewedorder;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\Review\Model\Getreviewedorder',
            'ArtM\Review\Model\ResourceModel\Getreviewedorder'
	    );
    }
}