<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Helper;

/**
 * Attributes management
 */
class AttributesChildren extends \Magento\Framework\App\Helper\AbstractHelper implements \Wyomind\DataFeedManager\Helper\AttributesInterface
{


    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function children($model, $options, $product, $reference)
    {


        $item=$model->checkReference($reference, $product);
        if ($item == null) {
            return "";
        }
        $productTypeId=$item->getTypeId();
        $type="_" . $productTypeId . "Children";
        $children=[];
        $identifier=(!isset($options['identifier'])) ? "sku" : $options['identifier'];
        $separator=(!isset($options['separator'])) ? "," : $options['separator'];
        $separator=$separator == "pipe" ? "|" : $separator;
        $separator=$separator == "comma" ? "," : $separator;
        $separator=$separator == "semi-colon" ? ";" : $separator;

        if (isset($model->$type[$item->getId()][$identifier])) {
            foreach ($model->$type[$item->getId()][$identifier] as $child) {
                $children[]=$child;
            }

            return implode($separator, $children);
        };

        return "";
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
