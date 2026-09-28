<?php

namespace Meetanshi\Sms\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SMSQueue
 * @package Meetanshi\Sms\Model\ResourceModel
 */
class SMSQueue extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('artm_sms_queue', 'entity_id');
    }
}