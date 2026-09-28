<?php

namespace ArtM\MsiPriority\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Config
{
    private const XML_PATH_ENABLED = 'msi_priority/generalproductcreate/enabled';
    private const XML_PATH_SYNC_BRANDS = 'msi_priority/generalproductcreate/sync_brands';
    private const XML_PATH_CHANNEL_CODE = 'msi_priority/generalproductcreate/channel_code';
    private const XML_PATH_CHANNEL_PRODUCT_ID = 'msi_priority/generalproductcreate/channel_product_id';
    private const XML_PATH_CHANNEL_SKU_PREFIX = 'msi_priority/generalproductcreate/channel_sku_prefix';
    private const XML_PATH_BASE_URL = 'msi_priority/generalproductcreate/base_url';
    private const XML_PATH_API_OWNER = 'msi_priority/generalproductcreate/api_owner';
    private const XML_PATH_API_KEY = 'msi_priority/generalproductcreate/api_key';
    private const XML_PATH_TIMEOUT = 'msi_priority/generalproductcreate/timeout';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    private $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getBaseUrl($storeId = null)
    {
        return rtrim((string) $this->scopeConfig->getValue(self::XML_PATH_BASE_URL, ScopeInterface::SCOPE_STORE, $storeId), '/');
    }

    public function getApiOwner($storeId = null)
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_API_OWNER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getApiKey($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $this->encryptor->decrypt($value);
    }

    public function getTimeout($storeId = null)
    {
        $timeout = (int) $this->scopeConfig->getValue(self::XML_PATH_TIMEOUT, ScopeInterface::SCOPE_STORE, $storeId);
        return $timeout > 0 ? $timeout : 30;
    }

    public function getChannelCode($storeId = null)
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_CHANNEL_CODE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getDefaultChannelProductId($storeId = null)
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_CHANNEL_PRODUCT_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getChannelSkuPrefix($storeId = null)
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_CHANNEL_SKU_PREFIX, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getSyncBrandValues($storeId = null)
    {
        $raw = (string) $this->scopeConfig->getValue(self::XML_PATH_SYNC_BRANDS, ScopeInterface::SCOPE_STORE, $storeId);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), static function ($value) {
            return $value !== '';
        }));
    }
}
