<?php
/**
 * Copyright © All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\NewArrivals\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED = 'artm_newarrivals/general/enabled';
    public const XML_PATH_DURATION_DAYS = 'artm_newarrivals/general/duration_days';
    public const ATTRIBUTE_CODE = 'newarrivals';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        if ($storeId === null) {
            return $this->scopeConfig->isSetFlag(
                self::XML_PATH_ENABLED,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getDurationDays(?int $storeId = null): int
    {
        if ($storeId === null) {
            $days = (int) $this->scopeConfig->getValue(
                self::XML_PATH_DURATION_DAYS,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        } else {
            $days = (int) $this->scopeConfig->getValue(
                self::XML_PATH_DURATION_DAYS,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        if ($days <= 0) {
            return 60;
        }
        return $days;
    }
}
