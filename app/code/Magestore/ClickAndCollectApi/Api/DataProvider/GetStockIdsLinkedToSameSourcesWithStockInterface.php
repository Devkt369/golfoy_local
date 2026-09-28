<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\DataProvider;

/**
 * Get Stock Ids Linked To Same Sources With Stock Data Provider Interface
 *
 * @api
 */
interface GetStockIdsLinkedToSameSourcesWithStockInterface
{
    /**
     * Get Stock Ids Linked To Same Sources With Stock
     *
     * @param int $stockId
     * @return array
     */
    public function execute(int $stockId): array;
}
