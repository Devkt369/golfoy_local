<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Location;

use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;
use Magestore\ClickAndCollectApi\Api\GetLocationScheduleInterface;
use Magestore\ClickAndCollectApi\Api\ScheduleRepositoryInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Psr\Log\LoggerInterface;

/**
 * Get location's schedule
 */
class GetLocationSchedule implements GetLocationScheduleInterface
{
    /**
     * @var ScheduleRepositoryInterface
     */
    protected $scheduleRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * GetLocationSchedule constructor.
     * @param ScheduleRepositoryInterface $scheduleRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScheduleRepositoryInterface $scheduleRepository,
        LoggerInterface $logger
    ) {
        $this->scheduleRepository = $scheduleRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(LocationInterface $location): ?ScheduleInterface
    {
        $schedule = null;
        if ($location->getScheduleId()) {
            try {
                $schedule = $this->scheduleRepository->get((int)$location->getScheduleId());
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $schedule = null;
            }
        }
        return $schedule;
    }
}
