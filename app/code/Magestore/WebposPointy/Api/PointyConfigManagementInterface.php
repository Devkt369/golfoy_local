<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Pointy - config management interface
 */
interface PointyConfigManagementInterface
{
    /**
     * Is Pointy Enabled
     *
     * @return bool
     */
    public function isPointyEnabled(): bool;

    /**
     * Get Client Auth Key
     *
     * @return string
     */
    public function getClientAuthKey(): string;

    /**
     * Is Sandbox Mode
     *
     * @return bool
     */
    public function isSandboxMode(): bool;

    /**
     * Get Manufacturer Barcode Attribute
     *
     * @return string
     */
    public function getManufacturerBarcodeAttribute(): string;
}
