<?php 
	namespace ArtM\ProductFeatures\Model\ResourceModel;
	class Getproductfeatures extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_productfeatures","id");
	 }
	}
 ?>
