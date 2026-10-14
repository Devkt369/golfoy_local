<?php
namespace WeserveCommerce\AjaxSearch\Model\Filter;

class StockFilter
{
    /**
     * Build search engine stock filter clause.
     *
     * Magento indexes stock under different field names depending on version/MSI.
     *
     * @param bool $onlyInStock
     * @return array
     */
    public function build(bool $onlyInStock = true): array
    {
        if (!$onlyInStock) {
            return [];
        }

        return [
            'bool' => [
                'should' => [
                    ['term' => ['is_out_of_stock' => 0]],
                    ['term' => ['is_in_stock' => 1]],
                    ['term' => ['stock_status' => 1]]
                ],
                'minimum_should_match' => 1
            ]
        ];
    }
}
