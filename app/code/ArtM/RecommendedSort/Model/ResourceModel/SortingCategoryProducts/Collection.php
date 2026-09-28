<?php
namespace ArtM\RecommendedSort\Model\ResourceModel\SortingCategoryProducts;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\RecommendedSort\Model\SortingCategoryProducts', 'ArtM\RecommendedSort\Model\ResourceModel\SortingCategoryProducts');
    }
}