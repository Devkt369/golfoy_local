<?php
namespace WeserveCommerce\AjaxSearch\Api;

interface PriceFieldResolverInterface
{
    /**
     * Resolve the price field name used in search index for given store context.
     *
     * @param int $storeId
     * @param array $sampleDocument
     * @return string
     */
    public function getSearchField(int $storeId, array $sampleDocument = []): string;
}
