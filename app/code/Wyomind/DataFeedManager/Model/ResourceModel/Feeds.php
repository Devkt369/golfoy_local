<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Model\ResourceModel;

/**
 * Class Feeds
 * @package Wyomind\DataFeedManager\Model\ResourceModel
 */
class Feeds extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public $_date;
    public function __construct(\Wyomind\DataFeedManager\Helper\Delegate $wyomind, \Magento\Framework\Model\ResourceModel\Db\Context $context)
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context);
    }
    /**
     * Initialize resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('datafeedmanager_feeds', 'id');
    }
    /**
     * @param $request
     * @return \Zend_Db_Statement_Interface
     */
    public function importDataFeed($request)
    {
        $connection = $this->getConnection('write');
        $request = str_replace("{{datafeedmanager_feeds}}", $this->getTable('datafeedmanager_feeds'), (string) $request);
        return $connection->query($request);
    }
}