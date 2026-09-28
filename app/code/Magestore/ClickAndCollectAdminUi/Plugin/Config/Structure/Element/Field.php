<?php

/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_ClickAndCollectAdminHtml
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\ClickAndCollectAdminUi\Plugin\Config\Structure\Element;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\ObjectManagerInterface;
use Magestore\ClickAndCollectAdminUi\Model\Config\Comment\Enabled;

/**
 * Model Field
 *
 * @module   ClickAndCollect
 * @author   Magestore Developer
 */
class Field
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * mapping path for field comment.
     *
     * @var array
     */
    protected $_mapPathFieldComments = [
        'carriers/clickandcollect/active' => Enabled::class
    ];

    /**
     * Field constructor.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Retrieve comment.
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field
     * @param \Closure $proceed
     * @param string $currentValue
     *
     * @return string
     */
    public function aroundGetComment(
        \Magento\Config\Model\Config\Structure\Element\Field $field,
        \Closure $proceed,
        $currentValue = ''
    ) {
        if (isset($this->_mapPathFieldComments[$field->getPath()])) {
            /** @var CommentInterface $commentModel */
            $commentModel = $this->_objectManager->create($this->_mapPathFieldComments[$field->getPath()]);

            return $commentModel->getCommentText($currentValue);
        }

        return $proceed($currentValue);
    }
}
