<?php 

namespace ArtM\Review\Model\ResourceModel\Cron;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\Review\Model\Cron',
            'ArtM\Review\Model\ResourceModel\Cron'
	    );
    }
}