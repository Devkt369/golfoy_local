<?php
namespace ArtM\AdminAI\Cron;

use ArtM\AdminAI\Model\Sync\ProductAnalyticsSync;
use Psr\Log\LoggerInterface;

class ProductAnalyticsSyncCron
{
    /** @var ProductAnalyticsSync */
    private $service;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ProductAnalyticsSync $service,
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
            $this->logger->error('ProductAnalyticsSyncCron failed', ['exception' => $e]);
        }
    }
}
