<?php 
namespace ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProducts;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\RecommendedFilter\Model\ArtmSortingProducts',
            'ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProducts'
	    );
    }
}