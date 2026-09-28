<?php 
namespace ArtM\Expertlist\Model;
class ExpertModel extends \Magento\Framework\Model\AbstractModel
{
	public function _construct(){
		$this->_init('ArtM\Expertlist\Model\ResourceModel\ExpertModel');
	}
}