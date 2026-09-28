<?php 
namespace ArtM\Loginbyphone\Model;
class Sendotp extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init("ArtM\Loginbyphone\Model\ResourceModel\Sendotp");
	}
}