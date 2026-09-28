<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Cron;

use ArtM\SFMC\Helper\Data;

class Product
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
        \ArtM\SFMC\Logger\ProductLogger $productLogger,
        Data $helperData
        )
    {
        $this->_helperData = $helperData;
        $this->logger = $logger;
        $this->productLogger = $productLogger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        if($this->_helperData->IsProductCronEnable()){
            $this->productLogger->info( "product cron started running" );
            $this->_helperData->ProcessProduct();
            $this->productLogger->info( "product cron stoped running" );
        }
    }
}