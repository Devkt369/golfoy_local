<?php 
namespace ArtM\Sizeroll\Model;
class Sizeroll extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\ArtM\Sizeroll\Model\ResourceModel\Sizeroll::class);
	}
}