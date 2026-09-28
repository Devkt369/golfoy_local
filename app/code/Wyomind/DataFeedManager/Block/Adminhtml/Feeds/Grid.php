<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Block\Adminhtml\Feeds;

/**
 * Class Grid
 * @package Wyomind\DataFeedManager\Block\Adminhtml\Feeds
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var null|\Wyomind\DataFeedManager\Model\ResourceModel\Feeds\CollectionFactory
     */
    protected $_collectionFactory = null;
    public $_framework = null;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Wyomind\DataFeedManager\Model\ResourceModel\Feeds\CollectionFactory $collectionFactory,
        /** @delegation off */
        array $data = []
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }
    protected function _construct()
    {
        parent::_construct();
        $this->setId('datafeedmanagerGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
    }
    /**
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    /**
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', ['header' => __('Id'), 'width' => '50px', 'index' => 'id']);
        $this->addColumn('name', ['header' => __('Filename'), 'index' => 'name']);
        $this->addColumn('type', ['header' => __('Type'), 'index' => 'type', 'renderer' => 'Wyomind\\DataFeedManager\\Block\\Adminhtml\\Feeds\\Renderer\\Type']);
        $this->addColumn('path', ['header' => __('Path'), 'index' => 'path']);
        $this->addColumn('link', ['header' => __('Link'), 'align' => 'left', 'index' => 'link', 'filter' => false, 'sortable' => false, 'renderer' => 'Wyomind\\DataFeedManager\\Block\\Adminhtml\\Feeds\\Renderer\\Link']);
        $this->addColumn('updated_at', ['header' => __('Update'), 'index' => 'updated_at', 'type' => 'datetime']);
        $this->addColumn('store_id', ['header' => __('Store'), 'index' => 'store_id', 'type' => 'store']);
        $this->addColumn('status', ['header' => __('Status'), 'index' => 'status', 'type' => 'options', 'options' => [1 => __('Enabled'), 0 => __('Disabled')]]);
        $this->addColumn('feed_status', ['header' => __('Status'), 'align' => 'left', 'renderer' => 'Wyomind\\DataFeedManager\\Block\\Adminhtml\\Progress\\Status', 'filter' => false, 'sortable' => false]);
        $this->addColumn('action', ['header' => __('Action'), 'type' => 'action', 'getter' => 'getId', 'filter' => false, 'sortable' => false, 'index' => 'id', 'header_css_class' => 'col-action', 'column_css_class' => 'col-action', 'renderer' => 'Wyomind\\DataFeedManager\\Block\\Adminhtml\\Feeds\\Renderer\\Action']);
        return parent::_prepareColumns();
    }
    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}