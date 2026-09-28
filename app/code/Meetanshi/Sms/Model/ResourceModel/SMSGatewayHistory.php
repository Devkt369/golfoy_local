<?php 
    namespace Meetanshi\Sms\Model\ResourceModel;
    class SMSGatewayHistory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
        public function _construct(){
            $this->_init("artm_smsgateway_sms_history","id");
        }
    }
?>
