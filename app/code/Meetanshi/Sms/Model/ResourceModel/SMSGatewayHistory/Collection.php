<?php 
namespace Meetanshi\Sms\Model\ResourceModel\SMSGatewayHistory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Meetanshi\Sms\Model\SMSGatewayHistory::class,
            \Meetanshi\Sms\Model\ResourceModel\SMSGatewayHistory::class
	    );
    }
}
