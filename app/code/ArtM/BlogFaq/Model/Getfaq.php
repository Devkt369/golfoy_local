<?php 
namespace ArtM\BlogFaq\Model;
class Getfaq extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\ArtM\BlogFaq\Model\ResourceModel\Getfaq::class);
	}
}