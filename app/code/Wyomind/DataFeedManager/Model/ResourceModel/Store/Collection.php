<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Model\ResourceModel\Store;

/**
 * @copyright Wyomind 2016
 */
class Collection extends \Magento\Store\Model\ResourceModel\Store\Collection
{


    /**
     * @return mixed
     */
    public function getFirstStoreId()
    {
        $this->getSelect()->limit(1);
        return $this->getFirstItem()->getStoreId();
    }
}
