<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Cron;

use ArtM\SFMC\Helper\Data;

class AbandonedCart
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
        \ArtM\SFMC\Logger\CartLogger $cartLogger,
        Data $helperData
        )
    {
        $this->_helperData = $helperData;
        $this->logger = $logger;
        $this->cartLogger = $cartLogger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        if($this->_helperData->isAbandonedCartCronEnable()){
            $this->cartLogger->info( "AbandonedCart cron started running " .date('Y-m-d') );
            $this->_helperData->ProcessAbandonedCart();
            $this->cartLogger->info( "AbandonedCart cron stoped running " .date('Y-m-d') );
        }
    }
}