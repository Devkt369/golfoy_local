<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Model\ResourceModel\Feeds;

/**
 * Class Collection
 * @package Wyomind\DataFeedManager\Model\ResourceModel\Feeds
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Wyomind\DataFeedManager\Model\Feeds', 'Wyomind\DataFeedManager\Model\ResourceModel\Feeds');
    }


    /**
     * @param $feedsIds
     * @return $this
     */
    public function getList($feedsIds)
    {
        if (!empty($feedsIds)) {
            $this->getSelect()->where("id IN (" . implode(',', $feedsIds) . ")");
        }
        return $this;
    }
}
