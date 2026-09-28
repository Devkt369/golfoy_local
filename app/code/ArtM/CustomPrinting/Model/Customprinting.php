<?php 
namespace ArtM\CustomPrinting\Model;
class Customprinting extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\ArtM\CustomPrinting\Model\ResourceModel\Customprinting::class);
	}
}