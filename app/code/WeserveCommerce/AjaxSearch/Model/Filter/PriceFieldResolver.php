<?php
namespace WeserveCommerce\AjaxSearch\Model\Filter;

use WeserveCommerce\AjaxSearch\Api\PriceFieldResolverInterface;

class PriceFieldResolver implements PriceFieldResolverInterface
{
    /**
     * Resolves the active price search field.
     *
     * @param int $storeId
     * @param array $sampleDocument
     * @return string
     */
    public function getSearchField(int $storeId, array $sampleDocument = []): string
    {
        if (isset($sampleDocument['customprice'])) {
            return 'customprice';
        }

        $storePriceField = sprintf('price_0_%d', $storeId);
        if (isset($sampleDocument[$storePriceField])) {
            return $storePriceField;
        }

        foreach (array_keys($sampleDocument) as $key) {
            if (strpos($key, 'price_') === 0) {
                return $key;
            }
        }

        return 'customprice';
    }
}
