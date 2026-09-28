<?php 
namespace ArtM\OrderFeedback\Model\ResourceModel\Maintable;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\OrderFeedback\Model\Maintable',
            'ArtM\OrderFeedback\Model\ResourceModel\Maintable'
	    );
    }
}
