<?php 
namespace ArtM\SSO\Model;
class Sendotp extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init("ArtM\SSO\Model\ResourceModel\Sendotp");
	}
}