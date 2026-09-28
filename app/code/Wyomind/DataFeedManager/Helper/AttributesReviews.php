<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Helper;

/**
 * Attributes management
 */
class AttributesReviews extends \Magento\Framework\App\Helper\AbstractHelper implements \Wyomind\DataFeedManager\Helper\AttributesInterface
{
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function reviewCount($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        
        $storeId = (!isset($options['store_id'])) ? $model->getStoreId() : $options['store_id'];

        if (isset($model->reviews[$item->getId()][$storeId]['count'])) {
            return $model->reviews[$item->getId()][$storeId]['count'];
        } else {
            return '';
        }
    }

    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function reviewAverage($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        
        $storeId = (!isset($options['store_id'])) ? $model->getStoreId() : $options['store_id'];
        $scoreBase = (!isset($options['score_base'])) ? '5' : $options['score_base'];
        
        if (isset($model->reviews[$item->getId()][$storeId]['score'])) {
            return number_format($model->reviews[$item->getId()][$storeId]['score'] * $scoreBase / 100, 2, '.', '');
        } else {
            return '';
        }
    }

    /**
     * @param $attributeCall
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed|null
     */
    public function proceedGeneric($attributeCall, $model, $options, $product, $reference)
    {
        return null;
    }
}
