<?php
namespace WeserveCommerce\AjaxSearch\Model\Search;

use WeserveCommerce\AjaxSearch\Api\SearchEngineAdapterInterface;

class ElasticsearchAdapter implements SearchEngineAdapterInterface
{
    /**
     * @var OpenSearchAdapter
     */
    private $openSearchAdapter;

    public function __construct(OpenSearchAdapter $openSearchAdapter)
    {
        $this->openSearchAdapter = $openSearchAdapter;
    }

    public function search(
        string $query,
        int $storeId,
        array $filters = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->openSearchAdapter->search($query, $storeId, $filters, $limit, $offset);
    }

    public function autocomplete(
        string $query,
        int $storeId,
        array $filters = [],
        int $limit = 8
    ): array {
        return $this->openSearchAdapter->autocomplete($query, $storeId, $filters, $limit);
    }

    public function isConnected(): bool
    {
        return $this->openSearchAdapter->isConnected();
    }
}
