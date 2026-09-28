<?php 
namespace ArtM\Common\Model\ResourceModel;
class Customcache extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	public function _construct(){
		 $this->_init("artm_custom_cache","id");
	}
}