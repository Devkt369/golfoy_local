<?php 
namespace ArtM\Celebration\Model;
class Celebration extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init("ArtM\Celebration\Model\ResourceModel\Celebration");
	}
}