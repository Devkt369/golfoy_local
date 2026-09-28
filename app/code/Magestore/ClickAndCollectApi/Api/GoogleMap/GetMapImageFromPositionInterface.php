<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\GoogleMap;

/**
 * Get map image from lat and lng
 *
 * @api
 */
interface GetMapImageFromPositionInterface
{
    /**
     * Get image from lat and lng
     *
     * @param string $lat
     * @param string $lng
     * @return array
     */
    public function execute(
        string $lat,
        string $lng
    ): array;
}
