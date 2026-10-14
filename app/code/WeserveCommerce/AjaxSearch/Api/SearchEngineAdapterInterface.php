<?php
namespace WeserveCommerce\AjaxSearch\Api;

interface SearchEngineAdapterInterface
{
    /**
     * Perform full search query directly against search engine.
     *
     * @param string $query
     * @param int $storeId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(
        string $query,
        int $storeId,
        array $filters = [],
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * Perform autocomplete search query directly against search engine.
     *
     * @param string $query
     * @param int $storeId
     * @param array $filters
     * @param int $limit
     * @return array
     */
    public function autocomplete(
        string $query,
        int $storeId,
        array $filters = [],
        int $limit = 8
    ): array;

    /**
     * Check if search backend is connected and healthy.
     *
     * @return bool
     */
    public function isConnected(): bool;
}
