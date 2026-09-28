<?php 
namespace ArtM\SSO\Model;
class Getkeys extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init("ArtM\SSO\Model\ResourceModel\Getkeys");
	}
}