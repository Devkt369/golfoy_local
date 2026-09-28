<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Cron;

use ArtM\SFMC\Helper\Data;

class AbandonedCoupon
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
        \ArtM\SFMC\Logger\AbandonedCouponLogger $AbandonedCouponLogger,
        Data $helperData
        )
    {
        $this->_helperData = $helperData;
        $this->logger = $logger;
        $this->abandonedCouponLogger = $AbandonedCouponLogger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        if($this->_helperData->isEnableAbandonedCoupon()){
            $this->abandonedCouponLogger->info( "Abandoned Coupon cron started running ".date('Y-m-d') );
            $this->_helperData->ProccessCouponForAbandonedCart();
            $this->abandonedCouponLogger->info( "Abandoned Coupon cron stoped running ".date('Y-m-d') );
        }
    }
}