<?php
namespace WeserveCommerce\AjaxSearch\Model;

use WeserveCommerce\AjaxSearch\Api\SearchServiceInterface;
use WeserveCommerce\AjaxSearch\Api\SearchEngineAdapterInterface;
use WeserveCommerce\AjaxSearch\Api\ProductFilterPolicyInterface;
use WeserveCommerce\AjaxSearch\Api\ProductResponseMapperInterface;
use WeserveCommerce\AjaxSearch\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

class SearchService implements SearchServiceInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SearchEngineAdapterInterface
     */
    private $searchAdapter;

    /**
     * @var ProductFilterPolicyInterface
     */
    private $filterPolicy;

    /**
     * @var ProductResponseMapperInterface
     */
    private $responseMapper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Config $config,
        SearchEngineAdapterInterface $searchAdapter,
        ProductFilterPolicyInterface $filterPolicy,
        ProductResponseMapperInterface $responseMapper,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->searchAdapter = $searchAdapter;
        $this->filterPolicy = $filterPolicy;
        $this->responseMapper = $responseMapper;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $query
     * @param int|null $storeId
     * @param int|null $limit
     * @return array
     */
    public function autocomplete(string $query, ?int $storeId = null, ?int $limit = null): array
    {
        $startTime = microtime(true);
        $storeId = $this->resolveStoreId($storeId);

        if (!$this->config->isAutocompleteEnabled($storeId)) {
            return [
                'query' => $query,
                'total' => 0,
                'products' => []
            ];
        }

        $cleanQuery = $this->sanitizeQuery($query, $storeId);
        $minLen = $this->config->getMinQueryLength($storeId);

        if (mb_strlen($cleanQuery) < $minLen && !empty($query)) {
            return [
                'query' => $query,
                'total' => 0,
                'products' => []
            ];
        }

        $effectiveLimit = $limit !== null && $limit > 0 ? $limit : $this->config->getAutocompleteLimit($storeId);
        $filters = $this->filterPolicy->buildFilters($storeId, []);

        $searchResult = $this->searchAdapter->autocomplete(
            $cleanQuery,
            $storeId,
            $filters,
            $effectiveLimit
        );

        $mappedProducts = [];
        foreach ($searchResult['hits'] as $hit) {
            $mappedProducts[] = $this->responseMapper->map($hit, $storeId);
        }

        $response = [
            'query' => $cleanQuery,
            'total' => $searchResult['total'],
            'products' => $mappedProducts
        ];

        if (!empty($searchResult['error'])) {
            $response['error'] = 'Search is temporarily unavailable.';
        }

        if ($this->config->isDebugMode($storeId)) {
            $totalMs = (int)round((microtime(true) - $startTime) * 1000);
            $response['meta'] = [
                'search_ms' => $searchResult['took_ms'] ?? 0,
                'total_ms' => $totalMs,
                'index' => $searchResult['index'] ?? null
            ];
        }

        return $response;
    }

    /**
     * @param string $query
     * @param int|null $storeId
     * @param array $filters
     * @param int $page
     * @param int|null $limit
     * @return array
     */
    public function search(
        string $query,
        ?int $storeId = null,
        array $filters = [],
        int $page = 1,
        ?int $limit = null
    ): array {
        $startTime = microtime(true);
        $storeId = $this->resolveStoreId($storeId);

        if (!$this->config->isEnabled($storeId)) {
            return [
                'query' => $query,
                'page' => $page,
                'limit' => $limit ?: 20,
                'total' => 0,
                'products' => []
            ];
        }

        $cleanQuery = $this->sanitizeQuery($query, $storeId);
        $effectiveLimit = $limit !== null && $limit > 0 ? $limit : $this->config->getSearchLimit($storeId);
        $page = max(1, $page);
        $offset = ($page - 1) * $effectiveLimit;

        $dslFilters = $this->filterPolicy->buildFilters($storeId, $filters);

        $searchResult = $this->searchAdapter->search(
            $cleanQuery,
            $storeId,
            $dslFilters,
            $effectiveLimit,
            $offset
        );

        $mappedProducts = [];
        foreach ($searchResult['hits'] as $hit) {
            $mappedProducts[] = $this->responseMapper->map($hit, $storeId);
        }

        $response = [
            'query' => $cleanQuery,
            'page' => $page,
            'limit' => $effectiveLimit,
            'total' => $searchResult['total'],
            'products' => $mappedProducts
        ];

        if (!empty($searchResult['error'])) {
            $response['error'] = 'Search is temporarily unavailable.';
        }

        if ($this->config->isDebugMode($storeId)) {
            $totalMs = (int)round((microtime(true) - $startTime) * 1000);
            $response['meta'] = [
                'search_ms' => $searchResult['took_ms'] ?? 0,
                'total_ms' => $totalMs
            ];
        }

        return $response;
    }

    private function resolveStoreId(?int $storeId): int
    {
        if ($storeId !== null && $storeId > 0) {
            return $storeId;
        }

        try {
            return (int)$this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            return 1;
        }
    }

    private function sanitizeQuery(string $query, int $storeId): string
    {
        $query = trim(strip_tags($query));
        $maxLen = $this->config->getMaxQueryLength($storeId);

        if (mb_strlen($query) > $maxLen) {
            $query = mb_substr($query, 0, $maxLen);
        }

        return $query;
    }
}
