<?php 
namespace ArtM\ProductFeatures\Model;
class Getproductfeatures extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\ArtM\ProductFeatures\Model\ResourceModel\Getproductfeatures::class);
	}
}