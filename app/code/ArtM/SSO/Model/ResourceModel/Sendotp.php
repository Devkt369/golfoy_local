<?php 
	namespace ArtM\SSO\Model\ResourceModel;
	class Sendotp extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_loginbyotp","id");
	 }
	}
 ?>
