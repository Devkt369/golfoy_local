<?php 
namespace ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProcessedProducts;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\RecommendedFilter\Model\ArtmSortingProcessedProducts',
            'ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProcessedProducts'
	    );
    }
}