<?php 
	namespace ArtM\Review\Model\ResourceModel;
	class Getreviewedorder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_review_module","id");
	 }
	}
 ?>
