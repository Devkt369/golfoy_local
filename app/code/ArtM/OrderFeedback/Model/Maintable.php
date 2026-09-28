<?php 
namespace ArtM\OrderFeedback\Model;
class Maintable extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init("ArtM\OrderFeedback\Model\ResourceModel\Maintable");
	}
}