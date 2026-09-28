<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\RecommendedSort\Cron;

use ArtM\RecommendedSort\Helper\Data;
use ArtM\RecommendedSort\Logger\Logger;

class QueueProcess
{
    protected $helperData;
    protected $logger;

    /**
     * Constructor
     * @param Data $helperData
     * @param Logger $logger
     */
    public function __construct(
        Data $helperData,
        Logger $logger
        )
    {
        $this->helperData = $helperData;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        if(!$this->helperData->isQueueProcessCronEnabled()) {
            $this->logger->info('Queue process cron is disabled');
            return;
        }
        $this->logger->info('Queue process started');
        $this->helperData->queueProductRecaclulatePointsProcess();
        $this->logger->info('Queue process completed');
    }
}