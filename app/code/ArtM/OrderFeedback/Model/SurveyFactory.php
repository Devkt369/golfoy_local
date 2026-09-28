<?php 
namespace ArtM\OrderFeedback\Model;
class SurveyFactory extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\ArtM\OrderFeedback\Model\ResourceModel\SurveyFactory::class);
	}
}