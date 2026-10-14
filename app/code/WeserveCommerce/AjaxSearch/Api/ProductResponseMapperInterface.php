<?php
namespace WeserveCommerce\AjaxSearch\Api;

interface ProductResponseMapperInterface
{
    /**
     * Map a raw search document hit into a clean, compact storefront array representation.
     *
     * @param array $hit
     * @param int $storeId
     * @return array
     */
    public function map(array $hit, int $storeId): array;
}
