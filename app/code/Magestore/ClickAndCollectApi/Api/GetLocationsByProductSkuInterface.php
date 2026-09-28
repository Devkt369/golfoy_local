<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Get locations by product sku
 *
 * @api
 */
interface GetLocationsByProductSkuInterface
{
    /**
     * Get location list by product sku
     *
     * @param string $productSku
     * @return array
     */
    public function execute(string $productSku): array;
}
