<?php
namespace ArtM\AdminAI\Cron;

use ArtM\AdminAI\Model\Sync\CustomerAnalyticsSync;
use Psr\Log\LoggerInterface;

class CustomerAnalyticsSyncCron
{
    /** @var CustomerAnalyticsSync */
    private $service;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        CustomerAnalyticsSync $service,
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
            $this->logger->error('CustomerAnalyticsSyncCron failed', ['exception' => $e]);
        }
    }
}
