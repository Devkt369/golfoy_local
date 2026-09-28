<?php
namespace ArtM\AdminAI\Cron;

use ArtM\AdminAI\Model\Sync\CustomerSegmentationSync;
use Psr\Log\LoggerInterface;

class CustomerSegmentationCron
{
    /** @var CustomerSegmentationSync */
    private $service;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        CustomerSegmentationSync $service,
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
            $this->logger->error('CustomerSegmentationCron failed', ['exception' => $e]);
        }
    }
}
