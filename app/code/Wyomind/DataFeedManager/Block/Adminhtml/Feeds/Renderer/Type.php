<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Renderer;

/**
 * Class Type
 * @package Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Renderer
 */
class Type extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public $_dataHelper = null;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Backend\Block\Context $context,
        /** @delegation off */
        array $data = []
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context, $data);
    }
    /**
     * Renders grid column
     * @param \Magento\Framework\DataObject $row
     * @return type
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return $this->_dataHelper->getExtFromType($row->getType());
    }
}