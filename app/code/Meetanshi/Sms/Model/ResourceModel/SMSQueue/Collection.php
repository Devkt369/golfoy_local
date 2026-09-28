<?php

namespace Meetanshi\Sms\Model\ResourceModel\SMSQueue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Meetanshi\Sms\Model\ResourceModel\SMSQueue
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Meetanshi\Sms\Model\SMSQueue::class,
            \Meetanshi\Sms\Model\ResourceModel\SMSQueue::class
        );
    }
}