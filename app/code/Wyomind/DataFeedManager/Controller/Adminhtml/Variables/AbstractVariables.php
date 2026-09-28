<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml\Variables;

/**
 * Class AbstractVariables
 * @package Wyomind\DataFeedManager\Controller\Adminhtml\Variables
 */
abstract class AbstractVariables extends \Wyomind\DataFeedManager\Controller\Adminhtml\AbstractAction
{
    /**
     * @var string
     */
    public $title = "Data Feed Manager > Custom Variables";
    /**
     * @var string
     */
    public $breadcrumbOne = "Data Feed Manager";
    /**
     * @var string
     */
    public $breadcrumbTwo = "Manage Custom Variables";
    /**
     * @var string
     */
    public $model = "Wyomind\DataFeedManager\Model\Variables";
    /**
     * @var string
     */
    public $errorDoesntExist = "This variable no longer exists.";
    /**
     * @var string
     */
    public $successDelete = "The variable has been deleted.";
    /**
     * @var string
     */
    public $msgModify = "Modify custom variable";
    /**
     * @var string
     */
    public $msgNew = "New custom variable";
    /**
     * @var string
     */
    public $registryName = "variables";
    /**
     * @var string
     */
    public $menu = "variables";
}
