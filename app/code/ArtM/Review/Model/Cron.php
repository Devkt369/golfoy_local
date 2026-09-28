<?php 
namespace ArtM\Review\Model;
class Cron extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init("ArtM\Review\Model\ResourceModel\Cron");
	}
}