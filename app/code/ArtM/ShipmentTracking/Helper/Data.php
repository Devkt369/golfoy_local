<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_ENABLE_ORDER_STATUS_CRON =
        'shipment_tracking/general/enable_order_status_cron';

    /**
     * Check if Order Status Cron is enabled.
     */
    public function isOrderStatusCronEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_ORDER_STATUS_CRON,
            ScopeInterface::SCOPE_STORE
        );
    }
}