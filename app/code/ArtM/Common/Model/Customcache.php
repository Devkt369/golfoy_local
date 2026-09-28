<?php 
namespace ArtM\Common\Model;
class Customcache extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\ArtM\Common\Model\ResourceModel\Customcache::class);
	}
}