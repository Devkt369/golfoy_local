<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Get location ordered by distance by stock id
 *
 * @api
 */
interface GetLocationsOrderedByDistanceInterface
{
    /**
     * Get location list sorted by distance
     *
     * @param string $address
     * @param float $radius
     * @param int $stockId
     * @return array
     */
    public function execute(
        string $address,
        float $radius,
        int $stockId
    ): array;
}
