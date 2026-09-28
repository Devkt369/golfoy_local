<?php 

namespace ArtM\RecommendedSort\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class SortingCategoryProducts extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_sorting_category_products','id');
    }
}

?>