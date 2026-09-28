<?php 
    namespace ArtM\OrderFeedback\Model\ResourceModel;
	class Maintable extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_orderfeedback","id");
	 }
	}
 ?>
