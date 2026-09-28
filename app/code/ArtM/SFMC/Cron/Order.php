<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Cron;

use ArtM\SFMC\Helper\Data;

class Order
{
    protected $helperData;
    protected $logger;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \ArtM\SFMC\Logger\OrderLogger $orderLogger,
        Data $helperData
        )
    {
        $this->_helperData = $helperData;
        $this->logger = $logger;
        $this->orderLogger = $orderLogger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        if($this->_helperData->IsOrderCronEnable()){
            $this->orderLogger->info( "order cron started running" );
            $this->_helperData->ProcessOrder();
            $this->orderLogger->info( "order cron stoped running" );
        }
    }
}