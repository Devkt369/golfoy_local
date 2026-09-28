<?php
namespace ArtM\RecommendedSort\Model\ResourceModel\SortingJob;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\RecommendedSort\Model\SortingJob', 'ArtM\RecommendedSort\Model\ResourceModel\SortingJob');
    }
}