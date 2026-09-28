<?php 
	namespace ArtM\BlogFaq\Model\ResourceModel;
	class Getfaq extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_blogfaq","id");
	 }
	}
 ?>
