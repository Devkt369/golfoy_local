<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\Locations;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;

/**
 * Identity for resolved Locations.
 */
class Identity implements IdentityInterface
{
    /**
     * @var string
     */
    const CACHE_TAG = 'inv_magestore_location';

    /**
     * Get identity for Locations.
     *
     * @param array $resolvedData
     *
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $ids = [];
        $items = $resolvedData['items'] ?? [];
        foreach ($items as $item) {
            $ids[] = sprintf('%s_%s', self::CACHE_TAG, $item[LocationInterface::LOCATION_ID]);
        }
        if (!empty($ids)) {
            array_unshift($ids, self::CACHE_TAG);
        }

        return $ids;
    }
}
