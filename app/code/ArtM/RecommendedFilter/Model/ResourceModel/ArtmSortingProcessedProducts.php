<?php 
	namespace ArtM\RecommendedFilter\Model\ResourceModel;
    class ArtmSortingProcessedProducts extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
        public function _construct(){
            $this->_init("artm_sorting_processed_products","id");
        }
    }
?>
