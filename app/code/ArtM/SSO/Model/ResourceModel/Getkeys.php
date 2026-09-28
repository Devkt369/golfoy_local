<?php 
	namespace ArtM\SSO\Model\ResourceModel;
	class Getkeys extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_sms_keys","id");
	 }
	}
 ?>
