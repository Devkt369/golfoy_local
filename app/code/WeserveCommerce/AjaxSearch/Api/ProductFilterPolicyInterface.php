<?php
namespace WeserveCommerce\AjaxSearch\Api;

interface ProductFilterPolicyInterface
{
    /**
     * Build filter array for search engine based on store context and request parameters.
     *
     * @param int $storeId
     * @param array $requestFilters
     * @return array
     */
    public function buildFilters(int $storeId, array $requestFilters = []): array;
}
