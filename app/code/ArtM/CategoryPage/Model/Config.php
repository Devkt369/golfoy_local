<?php
namespace ArtM\CategoryPage\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED = 'artm_categorypage/general/enabled';
    public const XML_PATH_SHOW_EMPTY = 'artm_categorypage/general/show_empty_categories';
    public const XML_PATH_ENABLE_BANNER = 'artm_categorypage/general/enable_banner';
    public const XML_PATH_ENABLE_IMAGES = 'artm_categorypage/general/enable_images';
    public const XML_PATH_SIDEBAR_WIDTH = 'artm_categorypage/general/sidebar_width';
    public const XML_PATH_PLACEHOLDER = 'artm_categorypage/general/default_placeholder_image';
    public const XML_PATH_CACHE_LIFETIME = 'artm_categorypage/general/cache_lifetime';
    public const XML_PATH_ANIMATION_SPEED = 'artm_categorypage/general/animation_speed';

    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function showEmptyCategories(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SHOW_EMPTY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function enableBanner(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_BANNER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function enableImages(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_IMAGES, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getSidebarWidth(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_SIDEBAR_WIDTH, ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getPlaceholderImage(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_PLACEHOLDER, ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getCacheLifetime(?int $storeId = null): int
    {
        return max(0, (int)$this->scopeConfig->getValue(self::XML_PATH_CACHE_LIFETIME, ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getAnimationSpeed(?int $storeId = null): int
    {
        return max(0, (int)$this->scopeConfig->getValue(self::XML_PATH_ANIMATION_SPEED, ScopeInterface::SCOPE_STORE, $storeId));
    }
}
