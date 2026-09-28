<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface ScheduleRepositoryInterface
{
    /**
     * Save Schedule Data
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface $schedule
     * @return \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface|void
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface $schedule
    ): \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;

    /**
     * Get Schedule data by given scheduleId. If you want to create plugin on get method
     *
     * Also you need to create separate plugin on getList method, because entity loading way is different
     * for these methods
     *
     * @param int $scheduleId
     * @return \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $scheduleId): \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;

    /**
     * Find Schedules by given SearchCriteria
     *
     * SearchCriteria is not required because load all schedules is useful case
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return \Magestore\ClickAndCollectApi\Api\Data\ScheduleSearchResultsInterface
     */
    public function getList(
        ?\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    ): \Magestore\ClickAndCollectApi\Api\Data\ScheduleSearchResultsInterface;

    /**
     * Delete the Schedule data by scheduleId. If schedule is not found do nothing
     *
     * @param int $scheduleId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $scheduleId): void;
}
