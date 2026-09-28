<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Model\Product;

/**
 * Class Collection
 * @package Wyomind\DataFeedManager\Model\Product
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    // n'existe plus dans Magento 2 !
    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }

    /**
     * @return $this
     */
    public function getCollection()
    {
        return $this;
    }
}
