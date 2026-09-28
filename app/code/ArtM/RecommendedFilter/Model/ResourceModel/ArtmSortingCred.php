<?php 
	namespace ArtM\RecommendedFilter\Model\ResourceModel;
    class ArtmSortingCron extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
        public function _construct(){
            $this->_init("artm_sorting_cron","id");
        }
    }
?>
