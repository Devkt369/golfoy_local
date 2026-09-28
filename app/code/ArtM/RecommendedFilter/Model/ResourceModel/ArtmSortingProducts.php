<?php 
	namespace ArtM\RecommendedFilter\Model\ResourceModel;
    class ArtmSortingProducts extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
        public function _construct(){
            $this->_init("artm_sorting_products","id");
        }
    }
?>
