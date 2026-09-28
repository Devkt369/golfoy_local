<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Pointy process management
 */
interface PointyManagementInterface
{
    /**
     * Sign up Pointy
     *
     * @param int $locationId
     * @return array
     */
    public function signupLocation(int $locationId): array;

    /**
     * Submit sales
     *
     * @param int $locationId
     * @return void
     */
    public function submitSales(int $locationId);

    /**
     * Submit Inventory
     *
     * @param int $locationId
     */
    public function submitInventory(int $locationId);
}
