<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model;

use Magestore\WebposPointy\Api\PointyConfigManagementInterface;
use Magestore\Webpos\Helper\Data as WebposHelper;

/**
 * Pointy - config management
 */
class PointyConfigManagement implements PointyConfigManagementInterface
{
    const CONFIG_POINTY_ENABLE = 'webpos/pointy_integration/enabled';
    const CONFIG_POINTY_CLIENT_AUTH_KEY = 'webpos/pointy_integration/client_auth_key';
    const CONFIG_POINTY_SANDBOX_MODE = 'webpos/pointy_integration/sandbox_mode';
    const CONFIG_POINTY_MANUFACTURER_BARCODE_ATTRIBUTE = 'webpos/pointy_integration/manufacturer_barcode_attribute';

    /**
     * @var WebposHelper
     */
    private $webposHelper;

    /**
     * PointyConfigManagement constructor.
     * @param WebposHelper $webposHelper
     */
    public function __construct(
        WebposHelper $webposHelper
    ) {
        $this->webposHelper = $webposHelper;
    }

    /**
     * @inheritDoc
     */
    public function isPointyEnabled(): bool
    {
        return $this->webposHelper->getStoreConfig(self::CONFIG_POINTY_ENABLE);
    }

    /**
     * @inheritDoc
     */
    public function getClientAuthKey(): string
    {
        if (!$this->isPointyEnabled()) {
            return '';
        }
        return $this->webposHelper->getStoreConfig(self::CONFIG_POINTY_CLIENT_AUTH_KEY);
    }

    /**
     * @inheritDoc
     */
    public function isSandboxMode(): bool
    {
        return $this->webposHelper->getStoreConfig(self::CONFIG_POINTY_SANDBOX_MODE);
    }

    /**
     * @inheritDoc
     */
    public function getManufacturerBarcodeAttribute(): string
    {
        if (!$this->isPointyEnabled()) {
            return '';
        }
        return $this->webposHelper->getStoreConfig(self::CONFIG_POINTY_MANUFACTURER_BARCODE_ATTRIBUTE);
    }
}
