<?php 
namespace ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingCred;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\RecommendedFilter\Model\ArtmSortingCred',
            'ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingCred'
	    );
    }
}