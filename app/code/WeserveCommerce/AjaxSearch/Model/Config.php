<?php
namespace WeserveCommerce\AjaxSearch\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'fastsearch/general/enabled';
    private const XML_PATH_AUTOCOMPLETE_ENABLED = 'fastsearch/general/autocomplete_enabled';
    private const XML_PATH_AUTOCOMPLETE_LIMIT = 'fastsearch/general/autocomplete_limit';
    private const XML_PATH_SEARCH_LIMIT = 'fastsearch/general/search_limit';
    private const XML_PATH_RESULTS_LOADING_MODE = 'fastsearch/general/results_loading_mode';
    private const XML_PATH_MIN_QUERY_LENGTH = 'fastsearch/general/min_query_length';
    private const XML_PATH_MAX_QUERY_LENGTH = 'fastsearch/general/max_query_length';
    private const XML_PATH_DEBUG_MODE = 'fastsearch/general/debug_mode';

    private const XML_PATH_USE_MAGENTO_CONFIG = 'fastsearch/engine/use_magento_config';
    private const XML_PATH_ENGINE_HOST = 'fastsearch/engine/host';
    private const XML_PATH_ENGINE_PORT = 'fastsearch/engine/port';
    private const XML_PATH_INDEX_PREFIX = 'fastsearch/engine/index_prefix';
    private const XML_PATH_TIMEOUT = 'fastsearch/engine/timeout';

    private const XML_PATH_HIDE_OUT_OF_STOCK = 'fastsearch/filtering/hide_out_of_stock';
    private const XML_PATH_FUZZY_SEARCH = 'fastsearch/filtering/fuzzy_search';
    private const XML_PATH_INDEX_ATTRIBUTES = 'fastsearch/indexing/attributes';
    private const XML_PATH_AUTOCOMPLETE_ATTRIBUTES = 'fastsearch/indexing/autocomplete_attributes';

    // Magento Catalog Search Config Paths
    private const XML_PATH_MAGENTO_ENGINE = 'catalog/search/engine';
    private const XML_PATH_MAGENTO_MIN_QUERY = 'catalog/search/min_query_length';
    private const XML_PATH_COMPOUND_WORD_SYNONYMS = 'fastsearch/synonyms/compound_word_synonyms';
    private const XML_PATH_MAGENTO_MAX_QUERY = 'catalog/search/max_query_length';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isAutocompleteEnabled(?int $storeId = null): bool
    {
        if (!$this->isEnabled($storeId)) {
            return false;
        }
        $val = $this->scopeConfig->getValue(self::XML_PATH_AUTOCOMPLETE_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
        return $val === null || (bool)$val;
    }

    public function getAutocompleteLimit(?int $storeId = null): int
    {
        $limit = (int)$this->scopeConfig->getValue(
            self::XML_PATH_AUTOCOMPLETE_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $limit > 0 ? $limit : 8;
    }

    public function getSearchLimit(?int $storeId = null): int
    {
        $limit = (int)$this->scopeConfig->getValue(
            self::XML_PATH_SEARCH_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $limit > 0 ? $limit : 20;
    }

    public function getResultsLoadingMode(?int $storeId = null): string
    {
        $mode = (string)$this->scopeConfig->getValue(
            self::XML_PATH_RESULTS_LOADING_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return in_array($mode, ['pagination', 'infinite_scroll'], true) ? $mode : 'pagination';
    }

    public function getMinQueryLength(?int $storeId = null): int
    {
        $val = $this->scopeConfig->getValue(self::XML_PATH_MIN_QUERY_LENGTH, ScopeInterface::SCOPE_STORE, $storeId);
        if ($val !== null && (int)$val > 0) {
            return (int)$val;
        }
        $magentoMin = (int)$this->scopeConfig->getValue(self::XML_PATH_MAGENTO_MIN_QUERY, ScopeInterface::SCOPE_STORE, $storeId);
        return $magentoMin > 0 ? $magentoMin : 1;
    }

    public function getMaxQueryLength(?int $storeId = null): int
    {
        $val = $this->scopeConfig->getValue(self::XML_PATH_MAX_QUERY_LENGTH, ScopeInterface::SCOPE_STORE, $storeId);
        if ($val !== null && (int)$val > 0) {
            return (int)$val;
        }
        $magentoMax = (int)$this->scopeConfig->getValue(self::XML_PATH_MAGENTO_MAX_QUERY, ScopeInterface::SCOPE_STORE, $storeId);
        return $magentoMax > 0 ? $magentoMax : 128;
    }

    public function isDebugMode(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSearchEngine(?int $storeId = null): string
    {
        $engine = (string)$this->scopeConfig->getValue(
            self::XML_PATH_MAGENTO_ENGINE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $engine ?: 'opensearch';
    }

    public function getEngineConfigPrefix(?int $storeId = null): string
    {
        $engine = $this->getSearchEngine($storeId);
        $supported = ['opensearch', 'elasticsearch7', 'elasticsearch8', 'elasticsearch6', 'elasticsearch5'];
        if (in_array($engine, $supported, true)) {
            return $engine;
        }

        return 'opensearch';
    }

    public function getHost(?int $storeId = null): string
    {
        $overrideHost = (string)$this->scopeConfig->getValue(self::XML_PATH_ENGINE_HOST, ScopeInterface::SCOPE_STORE, $storeId);
        if ($overrideHost !== '') {
            return $overrideHost;
        }

        $engine = $this->getEngineConfigPrefix($storeId);
        $magentoHost = (string)$this->scopeConfig->getValue("catalog/search/{$engine}_server_hostname", ScopeInterface::SCOPE_STORE, $storeId);
        return $magentoHost ?: 'localhost';
    }

    public function getPort(?int $storeId = null): int
    {
        $overridePort = (int)$this->scopeConfig->getValue(self::XML_PATH_ENGINE_PORT, ScopeInterface::SCOPE_STORE, $storeId);
        if ($overridePort > 0) {
            return $overridePort;
        }

        $engine = $this->getEngineConfigPrefix($storeId);
        $magentoPort = (int)$this->scopeConfig->getValue("catalog/search/{$engine}_server_port", ScopeInterface::SCOPE_STORE, $storeId);
        return $magentoPort > 0 ? $magentoPort : 9200;
    }

    public function getIndexPrefix(?int $storeId = null): string
    {
        $overridePrefix = (string)$this->scopeConfig->getValue(self::XML_PATH_INDEX_PREFIX, ScopeInterface::SCOPE_STORE, $storeId);
        if ($overridePrefix !== '') {
            return $overridePrefix;
        }

        $engine = $this->getEngineConfigPrefix($storeId);
        $magentoPrefix = (string)$this->scopeConfig->getValue("catalog/search/{$engine}_index_prefix", ScopeInterface::SCOPE_STORE, $storeId);
        return $magentoPrefix ?: 'magento2';
    }

    public function getTimeout(?int $storeId = null): int
    {
        $overrideTimeout = (int)$this->scopeConfig->getValue(self::XML_PATH_TIMEOUT, ScopeInterface::SCOPE_STORE, $storeId);
        if ($overrideTimeout > 0) {
            return $overrideTimeout;
        }

        $engine = $this->getEngineConfigPrefix($storeId);
        $magentoTimeout = (int)$this->scopeConfig->getValue("catalog/search/{$engine}_server_timeout", ScopeInterface::SCOPE_STORE, $storeId);
        return $magentoTimeout > 0 ? $magentoTimeout : 15;
    }

    public function isAuthEnabled(?int $storeId = null): bool
    {
        $engine = $this->getEngineConfigPrefix($storeId);
        return $this->scopeConfig->isSetFlag(
            "catalog/search/{$engine}_enable_auth",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getUsername(?int $storeId = null): string
    {
        $engine = $this->getEngineConfigPrefix($storeId);
        return (string)$this->scopeConfig->getValue(
            "catalog/search/{$engine}_username",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPassword(?int $storeId = null): string
    {
        $engine = $this->getEngineConfigPrefix($storeId);
        return (string)$this->scopeConfig->getValue(
            "catalog/search/{$engine}_password",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getServerBaseUrl(?int $storeId = null): string
    {
        $host = trim($this->getHost($storeId));
        $port = $this->getPort($storeId);

        if (preg_match('#^https?://#i', $host)) {
            $parts = parse_url($host);
            $scheme = $parts['scheme'] ?? 'http';
            $hostname = $parts['host'] ?? $host;
            $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
            $portSegment = '';
            if (!empty($parts['port'])) {
                $portSegment = ':' . (int)$parts['port'];
            }
            return sprintf('%s://%s%s%s', $scheme, $hostname, $portSegment, $path);
        }

        $scheme = ($port === 443) ? 'https' : 'http';
        return sprintf('%s://%s:%d', $scheme, $host, $port);
    }

    public function hideOutOfStock(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_HIDE_OUT_OF_STOCK,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isFuzzySearchEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FUZZY_SEARCH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getQuickSearchAttributes(?int $storeId = null): array
    {
        return array_values(array_unique(array_merge(
            ['name', 'sku'],
            $this->getConfiguredAttributes(self::XML_PATH_INDEX_ATTRIBUTES, $storeId)
        )));
    }

    public function getAutocompleteAttributes(?int $storeId = null): array
    {
        return $this->getConfiguredAttributes(self::XML_PATH_AUTOCOMPLETE_ATTRIBUTES, $storeId);
    }

    public function getIndexedAttributes(?int $storeId = null): array
    {
        return array_values(array_unique(array_merge(
            $this->getQuickSearchAttributes($storeId),
            $this->getAutocompleteAttributes($storeId)
        )));
    }

    private function getConfiguredAttributes(string $path, ?int $storeId): array
    {
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        $attributes = is_array($value) ? $value : preg_split('/\s*,\s*/', (string)$value, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(array_map(function ($attributeCode) {
            if (!is_string($attributeCode) && !is_numeric($attributeCode)) {
                return null;
            }

            $attributeCode = trim((string)$attributeCode);
            return $attributeCode !== '' ? $attributeCode : null;
        }, $attributes))));
    }

    public function getQuickSearchIndexName(?int $storeId = null): string
    {
        $prefix = rtrim($this->getIndexPrefix($storeId), '_');
        $storeId = $storeId !== null && $storeId > 0 ? (int)$storeId : 1;

        return ($prefix ?: 'golfoy') . '_store_' . $storeId . '_product';
    }

    public function getProductIndexPattern(?int $storeId = null): string
    {
        return $this->getQuickSearchIndexName($storeId);
    }

    public function getCompoundWordSynonyms(int $storeId): array
    {
        $raw = (string)$this->scopeConfig->getValue(
            self::XML_PATH_COMPOUND_WORD_SYNONYMS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $synonyms = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '=') === false) {
                continue;
            }
            [$term, $variant] = array_map('trim', explode('=', $line, 2));
            if ($term === '' || $variant === '') {
                continue;
            }
            $synonyms[mb_strtolower($term)] = $variant;
        }

        return $synonyms;
    }
}
