<?php 
namespace ArtM\RecommendedFilter\Model;
class ArtmSortingProducts extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init("ArtM\RecommendedFilter\Model\ResourceModel\ArtmSortingProducts");
	}
}