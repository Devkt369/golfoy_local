<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Api;

/**
 * Get latitude and longitude object from address
 *
 * @api
 */
interface GetLatLngFromAddressInterface
{
    /**
     * Get latitude and longitude object from address
     *
     * @param string $address
     * @return \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface
     */
    public function execute(
        string $address
    ): \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
}
