<?php 
	namespace ArtM\RecommendedFilter\Model\ResourceModel;
    class ArtmSortingCronLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
        public function _construct(){
            $this->_init("artm_sorting_cron_log","id");
        }
    }
?>
