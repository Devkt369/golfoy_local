<?php 
	namespace ArtM\Loginbyphone\Model\ResourceModel;
	class Getkeys extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_sms_getkeys","id");
	 }
	}
 ?>
