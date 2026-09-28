<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Cron;

use ArtM\CustomerSurvey\Model\Analytics\Aggregator;
use Psr\Log\LoggerInterface;

class AggregateAnalytics
{
    /**
     * @var Aggregator
     */
    private $aggregator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Aggregator $aggregator,
        LoggerInterface $logger
    ) {
        $this->aggregator = $aggregator;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            $this->aggregator->aggregate();
        } catch (\Throwable $e) {
            $this->logger->error('CustomerSurvey aggregate analytics cron failed: ' . $e->getMessage());
        }
    }
}
