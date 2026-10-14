<?php
namespace WeserveCommerce\AjaxSearch\Api;

interface ProductIndexResolverInterface
{
    /**
     * Dynamically resolve the search index pattern for given store ID.
     *
     * @param int $storeId
     * @return string
     */
    public function resolve(int $storeId): string;
}
