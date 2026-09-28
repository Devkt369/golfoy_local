<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml\Feeds;

/**
 * Class AbstractFeeds
 * @package Wyomind\DataFeedManager\Controller\Adminhtml\Feeds
 */
abstract class AbstractFeeds extends \Wyomind\DataFeedManager\Controller\Adminhtml\AbstractAction
{
    /**
     * @var string
     */
    public $title = "Data Feed Manager > Data Feeds";
    /**
     * @var string
     */
    public $breadcrumbOne = "Data Feed Manager";
    /**
     * @var string
     */
    public $breadcrumbTwo = "Manage Data Feeds";
    /**
     * @var string
     */
    public $model = "Wyomind\DataFeedManager\Model\Feeds";
    /**
     * @var string
     */
    public $errorDoesntExist = "This data feed doesn't exist anymore.";
    /**
     * @var string
     */
    public $successDelete = "The data feed has been deleted.";
    /**
     * @var string
     */
    public $msgModify = "Modify data feed";
    /**
     * @var string
     */
    public $msgNew = "New data feed";
    /**
     * @var string
     */
    public $registryName = "data_feed";
    /**
     * @var string
     */
    public $menu = "feeds";
}
