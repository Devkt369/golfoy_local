<?php 
namespace Meetanshi\Sms\Model;
class SMSGatewayHistory extends \Magento\Framework\Model\AbstractModel{
	public function _construct(){
		$this->_init(\Meetanshi\Sms\Model\ResourceModel\SMSGatewayHistory::class);
	}
}