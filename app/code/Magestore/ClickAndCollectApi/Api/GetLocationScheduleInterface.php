<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Get location's schedule
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface GetLocationScheduleInterface
{
    /**
     * Get location's schedule
     *
     * @param \Magestore\Webpos\Api\Data\Location\LocationInterface $location
     * @return Data\ScheduleInterface|null
     */
    public function execute(
        \Magestore\Webpos\Api\Data\Location\LocationInterface $location
    ): ?\Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;
}
