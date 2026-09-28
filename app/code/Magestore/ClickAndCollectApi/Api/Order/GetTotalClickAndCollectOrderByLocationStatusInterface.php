<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Order;

/**
 * Get total order by location id and status to summary report
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface GetTotalClickAndCollectOrderByLocationStatusInterface
{
    /**
     * Execute by location and status
     *
     * @param int $locationId
     * @param array $statuses
     * @return int
     */
    public function execute(
        int $locationId,
        array $statuses
    ): int;
}
