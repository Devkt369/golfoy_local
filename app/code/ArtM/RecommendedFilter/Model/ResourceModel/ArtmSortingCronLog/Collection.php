<?php 
namespace ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingCronLog;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\RecommendedFilter\Model\ArtmSortingCronLog',
            'ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingCronLog'
	    );
    }
}