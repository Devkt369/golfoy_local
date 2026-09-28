<?php 
namespace ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProcessedProductsTemp;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\RecommendedFilter\Model\ArtmSortingProcessedProductsTemp',
            'ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProcessedProductsTemp'
	    );
    }
}