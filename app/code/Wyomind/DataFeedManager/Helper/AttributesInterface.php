<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Helper;

/**
 * Interface AttributesInterface
 * @package Wyomind\DataFeedManager\Helper
 */
interface AttributesInterface
{
    /**
     * @param $attributeCall
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed
     */
    public function proceedGeneric($attributeCall, $model, $options, $product, $reference);
}
