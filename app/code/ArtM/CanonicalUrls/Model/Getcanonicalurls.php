<?php 
namespace ArtM\CanonicalUrls\Model;
class Getcanonicalurls extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\ArtM\CanonicalUrls\Model\ResourceModel\Getcanonicalurls::class);
	}
}