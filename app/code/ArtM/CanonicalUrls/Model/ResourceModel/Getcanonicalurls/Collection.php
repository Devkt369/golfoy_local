<?php 
namespace ArtM\CanonicalUrls\Model\ResourceModel\Getcanonicalurls;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
           \ArtM\CanonicalUrls\Model\Getcanonicalurls::class,
            \ArtM\CanonicalUrls\Model\ResourceModel\Getcanonicalurls::class
	    );
    }
}
