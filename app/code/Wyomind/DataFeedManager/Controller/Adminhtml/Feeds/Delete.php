<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml\Feeds;

/**
 * Delete action
 */
class Delete extends \Wyomind\DataFeedManager\Controller\Adminhtml\Feeds\AbstractFeeds
{
    /**
     * Execute action
     * @return void
     */
    public function execute()
    {
        return parent::delete();
    }
}
