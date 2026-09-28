<?php 
namespace ArtM\BlogEsg\Model;
class Getentries extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\ArtM\BlogEsg\Model\ResourceModel\Getentries::class);
	}
}