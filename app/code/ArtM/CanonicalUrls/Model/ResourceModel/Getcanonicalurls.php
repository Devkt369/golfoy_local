<?php 
	namespace ArtM\CanonicalUrls\Model\ResourceModel;
	class Getcanonicalurls extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	 public function _construct(){
		 $this->_init("artm_canonicalurls","id");
	 }
	}
 ?>
