<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Cron;

use ArtM\SFMC\Helper\Data;

class Customer
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
        \ArtM\SFMC\Logger\Logger $sfmcLogger,
        \ArtM\SFMC\Logger\OrderLogger $orderLogger,
        \ArtM\SFMC\Logger\ProductLogger $productLogger,
        Data $helperData
        )
    {
        $this->_helperData = $helperData;
        $this->logger = $logger;
        $this->_SfmcLogger = $sfmcLogger;
        $this->orderLogger = $orderLogger;
        $this->productLogger = $productLogger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        if($this->_helperData->IsCustomerCronEnable()){
            $this->_SfmcLogger->info( "customer cron started running" );
            $this->_helperData->ProcessCustomer();
            $this->_SfmcLogger->info( "customer cron stoped running" );
        }
        // if($this->_helperData->IsOrderCronEnable()){
        //     $this->orderLogger->info( "order cron started running" );
        //     $this->_helperData->ProcessOrder();
        //     $this->orderLogger->info( "order cron stoped running" );
        // }
        // if($this->_helperData->IsProductCronEnable()){
        //     $this->productLogger->info( "product cron started running" );
        //     $this->_helperData->ProcessProduct();
        //     $this->productLogger->info( "product cron stoped running" );
        // }
    }
}