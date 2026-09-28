<?php 
	namespace ArtM\Celebration\Model\ResourceModel;
	class Celebration extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_celebration","id");
	 }
	}
 ?>
