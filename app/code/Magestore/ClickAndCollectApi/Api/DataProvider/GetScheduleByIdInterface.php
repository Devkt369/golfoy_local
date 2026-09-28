<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\DataProvider;

/**
 * Get schedule by data provider
 *
 * @api
 */
interface GetScheduleByIdInterface
{
    /**
     * Get Schedule data by given scheduleId.
     *
     * @param int $scheduleId
     * @return \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(int $scheduleId): \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;
}
