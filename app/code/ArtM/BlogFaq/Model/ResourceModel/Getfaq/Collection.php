<?php 
namespace ArtM\BlogFaq\Model\ResourceModel\Getfaq;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
           \ArtM\BlogFaq\Model\Getfaq::class,
            \ArtM\BlogFaq\Model\ResourceModel\Getfaq::class
	    );
    }
}
