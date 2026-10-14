<?php
namespace WeserveCommerce\AjaxSearch\Block;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use WeserveCommerce\AjaxSearch\Api\SearchServiceInterface;
use WeserveCommerce\AjaxSearch\Model\Config;

class SearchResults extends Template
{
    private $searchService;
    private $storeManager;
    private $priceCurrency;
    private $config;
    private $logger;
    private $searchData;
    private $searchQuery;
    private $searchFailed = false;
    private $searchPage;

    public function __construct(
        Template\Context $context,
        SearchServiceInterface $searchService,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        Config $config,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->searchService = $searchService;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->config = $config;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    public function hasSearchError(): bool
    {
        return $this->searchFailed;
    }

    public function getSearchResults(string $query): array
    {
        $query = trim($query);
        if ($this->searchData !== null && $this->searchQuery === $query) {
            return $this->searchData;
        }

        $this->searchQuery = $query;
        $page = (int)$this->getRequest()->getParam('page', 1);
        $page = max(1, $page);
        $this->searchPage = $page;
        $this->searchData = [
            'total' => 0,
            'products' => [],
            'page' => $page
        ];
        if ($query === '') {
            return $this->searchData;
        }

        try {
            $storeId = (int)$this->storeManager->getStore()->getId();
            $this->searchData = $this->searchService->search(
                $query,
                $storeId,
                [],
                $page,
                $this->config->getSearchLimit($storeId)
            );
            $this->searchFailed = !empty($this->searchData['error']);
        } catch (\Throwable $exception) {
            $this->searchFailed = true;
            $this->logger->error('Unable to render OpenSearch results page: ' . $exception->getMessage());
        }

        return $this->searchData;
    }

    public function getCurrentPage(): int
    {
        if ($this->searchPage === null) {
            $page = (int)$this->getRequest()->getParam('page', 1);
            $this->searchPage = max(1, $page);
        }

        return $this->searchPage;
    }

    public function getResultsLoadingMode(): string
    {
        return $this->config->getResultsLoadingMode(
            (int)$this->storeManager->getStore()->getId()
        );
    }

    public function getSearchLimit(): int
    {
        return $this->config->getSearchLimit(
            (int)$this->storeManager->getStore()->getId()
        );
    }

    public function getAutocompleteUrl(): string
    {
        return $this->getUrl('ajaxsearch/search/autocomplete');
    }

    public function getPageUrl(string $query, int $page): string
    {
        return $this->getUrl('catalogsearch/result', [
            '_query' => [
                'q' => $query,
                'page' => $page
            ]
        ]);
    }

    public function formatPrice($price): string
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        return $this->priceCurrency->format(
            (float)$price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $storeId
        );
    }

}
