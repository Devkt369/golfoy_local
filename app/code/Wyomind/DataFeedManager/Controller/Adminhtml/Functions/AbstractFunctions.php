<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml\Functions;

/**
 * Delete action
 */
abstract class AbstractFunctions extends \Wyomind\DataFeedManager\Controller\Adminhtml\AbstractAction
{
    /**
     * @var string
     */
    public $title = "Data Feed Manager > Custom Functions";
    /**
     * @var string
     */
    public $breadcrumbOne = "Data Feed Manager";
    /**
     * @var string
     */
    public $breadcrumbTwo = "Manage Custom Functions";
    /**
     * @var string
     */
    public $model = "Wyomind\DataFeedManager\Model\Functions";
    /**
     * @var string
     */
    public $errorDoesntExist = "This function doesn't exist anymore.";
    /**
     * @var string
     */
    public $successDelete = "The function has been deleted.";
    /**
     * @var string
     */
    public $msgModify = "Modify custom function";
    /**
     * @var string
     */
    public $msgNew = "New custom function";
    /**
     * @var string
     */
    public $registryName = "function";
    /**
     * @var string
     */
    public $menu = "functions";
}
