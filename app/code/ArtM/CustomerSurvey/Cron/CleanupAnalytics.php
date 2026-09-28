<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Cron;

use ArtM\CustomerSurvey\Model\Analytics\Cleanup;
use Psr\Log\LoggerInterface;

class CleanupAnalytics
{
    /**
     * @var Cleanup
     */
    private $cleanup;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Cleanup $cleanup,
        LoggerInterface $logger
    ) {
        $this->cleanup = $cleanup;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            $this->cleanup->clean();
        } catch (\Throwable $e) {
            $this->logger->error('CustomerSurvey cleanup analytics cron failed: ' . $e->getMessage());
        }
    }
}
