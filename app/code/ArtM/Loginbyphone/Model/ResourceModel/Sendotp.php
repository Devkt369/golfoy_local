<?php 
	namespace ArtM\Loginbyphone\Model\ResourceModel;
	class Sendotp extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_loginbyphone","id");
	 }
	}
 ?>
