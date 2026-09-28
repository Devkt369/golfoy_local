<?php 
namespace ArtM\ProductFeatures\Model\ResourceModel\Getproductfeatures;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
           \ArtM\ProductFeatures\Model\Getproductfeatures::class,
            \ArtM\ProductFeatures\Model\ResourceModel\Getproductfeatures::class
	    );
    }
}
