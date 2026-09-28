<?php 
	namespace ArtM\BlogEsg\Model\ResourceModel;
	class Getentries extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_blogesg","id");
	 }
	}
 ?>
