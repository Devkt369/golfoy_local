<?php
namespace ArtM\AdminAI\Cron;

use ArtM\AdminAI\Model\Sync\SalesAggregateSync;
use Psr\Log\LoggerInterface;

class SalesAggregateSyncCron
{
    /** @var SalesAggregateSync */
    private $service;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        SalesAggregateSync $service,
        LoggerInterface $logger
    ) {
        $this->service = $service;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            $this->service->execute();
        } catch (\Throwable $e) {
            $this->logger->error('SalesAggregateSyncCron failed', ['exception' => $e]);
        }
    }
}
