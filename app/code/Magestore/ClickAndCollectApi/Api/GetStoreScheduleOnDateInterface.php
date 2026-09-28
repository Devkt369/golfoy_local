<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Get store's schedule on date
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface GetStoreScheduleOnDateInterface
{
    const STATUS = 'status';
    const OPEN = 'open';
    const CLOSE = 'close';

    const HOUR = 'hour';
    const MINUTE = 'minute';

    /**
     * Get store's schedule on date
     *
     * @param \DateTime $date
     * @param \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface|null $schedule
     * @param \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface[] $specialDays
     * @return array|null
     */
    public function execute(
        \DateTime $date,
        \Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface $schedule = null,
        array $specialDays = []
    ): ?array;
}
