<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Block\Adminhtml\Variables;

/**
 * Prepare the edit page
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    public $_coreRegistry = null;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Backend\Block\Widget\Context $context,
        /** @delegation off */
        array $data = []
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context, $data);
    }
    /**
     *
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Wyomind_DataFeedManager';
        $this->_controller = 'adminhtml_variables';
        parent::_construct();
        $this->removeButton('reset');
        $this->removeButton('save');
        $this->addButton('save', ['label' => __('Save'), 'class' => 'save', 'onclick' => "jQuery('#edit_form').submit();"]);
    }
}