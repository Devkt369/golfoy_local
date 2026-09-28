<?php 
namespace ArtM\OrderFeedback\Model\ResourceModel;
class SurveyFactory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
	public function _construct(){
		 $this->_init("artm_survey_results","id");
	}
}