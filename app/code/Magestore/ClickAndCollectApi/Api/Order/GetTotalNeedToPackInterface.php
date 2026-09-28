<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Order;

/**
 * Get total order need to pack to summary report
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface GetTotalNeedToPackInterface
{
    /**
     * Execute by location, from and to
     *
     * @param int $locationId
     * @param string $from
     * @param string $to
     * @return int
     */
    public function execute(
        int $locationId,
        string $from,
        string $to
    ): int;
}
