<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;

/**
 * Sort Location by distance
 *
 * @api
 */
interface SortLocationsAlgorithmByDistanceInterface
{
    /**
     * Sort Location algorithm just iterates over all the location one by one in distance order
     *
     * @param LatLngInterface $currentLatLngAddress
     * @param array $locationCollection
     * @param float $radius
     * @return array
     */
    public function execute(
        LatLngInterface $currentLatLngAddress,
        array $locationCollection,
        float $radius = null
    ): array;
}
