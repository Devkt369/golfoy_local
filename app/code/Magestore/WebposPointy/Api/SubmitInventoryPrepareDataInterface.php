<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Prepare data for submit inventory
 */
interface SubmitInventoryPrepareDataInterface
{
    const PRODUCTS = 'products';
    const DELETED_PRODUCTS = 'deleted_products';
    
    /**
     * Prepare data for submit inventory
     *
     * @param \Magestore\Webpos\Api\Data\Location\LocationInterface $location
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magestore\Webpos\Api\Data\Location\LocationInterface $location): array;
}
