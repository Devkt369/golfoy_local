<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\ClickAndCollectAdminUi\Model\Location\Schedule;

use Magestore\ClickAndCollectApi\Api\ScheduleRepositoryInterface;

/**
 * Class Source
 *
 * Source model
 */
class Schedule implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var ScheduleRepositoryInterface
     */
    protected $scheduleRepository;

    /**
     * Source constructor.
     * @param ScheduleRepositoryInterface $scheduleRepository
     */
    public function __construct(
        ScheduleRepositoryInterface $scheduleRepository
    ) {
        $this->scheduleRepository = $scheduleRepository;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $collection = $this->scheduleRepository->getList();
        $options = [];
        $options[] = [
            'label' => __('--- Please select a Schedule ---'),
            'value' => ''
        ];
        $items = $collection->getItems();
        if (count($items)) {
            foreach ($items as $schedule) {
                $options[] = ['value' => $schedule->getScheduleId(), 'label' => $schedule->getScheduleName()];
            }
        }
        return $options;
    }
}
