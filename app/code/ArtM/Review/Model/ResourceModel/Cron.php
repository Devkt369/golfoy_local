<?php 
	namespace ArtM\Review\Model\ResourceModel;
	class Cron extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_review_cron","id");
	 }
	}
 ?>
