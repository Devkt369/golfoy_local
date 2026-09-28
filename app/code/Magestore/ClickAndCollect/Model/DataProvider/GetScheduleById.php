<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\DataProvider;

use Magestore\ClickAndCollectApi\Api\DataProvider\GetScheduleByIdInterface;
use Magestore\ClickAndCollectApi\Api\ScheduleRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;

/**
 * Class GetScheduleById
 *
 * Use for save schedule when query by id
 */
class GetScheduleById implements GetScheduleByIdInterface
{
    /**
     * @var ScheduleInterface[]
     */
    protected $scheduleLoaded = [];

    /**
     * @var ScheduleRepositoryInterface
     */
    protected $scheduleRepository;

    /**
     * GetScheduleById constructor.
     *
     * @param ScheduleRepositoryInterface $scheduleRepository
     */
    public function __construct(
        ScheduleRepositoryInterface $scheduleRepository
    ) {
        $this->scheduleRepository = $scheduleRepository;
    }

    /**
     * Get Schedule data by given scheduleId.
     *
     * @param int $scheduleId
     * @return \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(int $scheduleId): \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface
    {
        if (!isset($this->scheduleLoaded[$scheduleId])) {
            $this->scheduleLoaded[$scheduleId] = $this->scheduleRepository->get($scheduleId);
        }
        return $this->scheduleLoaded[$scheduleId];
    }
}
