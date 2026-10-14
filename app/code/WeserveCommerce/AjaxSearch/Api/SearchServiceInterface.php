<?php
namespace WeserveCommerce\AjaxSearch\Api;

interface SearchServiceInterface
{
    /**
     * Autocomplete suggestion search endpoint interface.
     *
     * @param string $query
     * @param int|null $storeId
     * @param int|null $limit
     * @return array
     */
    public function autocomplete(string $query, ?int $storeId = null, ?int $limit = null): array;

    /**
     * Full search endpoint interface.
     *
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
    ): array;
}
