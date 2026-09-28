<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab;

/**
 * Categories tab
 */
class Categories extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    public $_categoryCollection;
    public $_directoryReader = null;
    public $_framework = null;
    public $objectManager = null;
    public $_tree = null;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        /** @delegation off */
        array $data = []
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context, $registry, $formFactory, $data);
    }
    /**
     * @return mixed
     */
    public function getFeedTaxonomy()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getTaxonomy();
    }
    /**
     * @return mixed
     */
    public function getCategoryFilter()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getCategoryFilter();
    }
    /**
     * @return mixed
     */
    public function getCategoryType()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getCategoryType();
    }
    /**
     * @return mixed
     */
    public function getDFMCategories()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getCategories();
    }
    /**
     * @param $directory
     * @return mixed
     */
    public function dirFiles($directory)
    {
        $dir = dir($directory);
        //Open Directory
        while (false !== ($file = $dir->read())) {
            //Reads Directory
            $extension = substr($file, strrpos($file, '.'));
            // Gets the File Extension
            if ($extension == ".txt") {
                // Extensions Allowed
                $filesAll[$file] = $file;
            }
            // Store in Array
        }
        $dir->close();
        // Close Directory
        asort($filesAll);
        // Sorts the Array
        return $filesAll;
    }
    /**
     * @return array
     */
    public function getAvailableTaxonomies()
    {
        $controllerModule = $this->getRequest()->getControllerModule();
        $directory = $this->_directoryReader->getModuleDir('', $controllerModule) . "/data/Google/Taxonomies/";
        if (file_exists($directory)) {
            return $this->dirFiles($directory);
        } else {
            return [];
        }
    }
    /**
     * @return false|string
     */
    public function getJsonTree()
    {
        $treeCategories = $this->_tree->getTree();
        return json_encode($treeCategories);
    }
    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('');
        $form->setValues($model->getData());
        $this->setForm($form);
        $this->setTemplate('edit/categories.phtml');
        return parent::_prepareForm();
    }
    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->_coreRegistry->registry('data_feed')->getStoreId();
    }
    /**
     * @return mixed
     */
    public function getCategories()
    {
        $tmp = $this->_categoryCollection->create();
        return $tmp->setStoreId($this->getStoreId())->addAttributeToSelect(['name'])->addAttributeToSort('path', 'ASC');
    }
    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Categories');
    }
    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Categories');
    }
    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }
    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}