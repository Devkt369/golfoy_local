<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Block\Adminhtml\Feeds;

/**
 * Class Preview
 * @package Wyomind\DataFeedManager\Block\Adminhtml\Feeds
 */
class Preview extends \Magento\Backend\Block\Template
{
    public $_dfmModel = null;
    public $_framework = null;
    /**
     * @var null
     */
    public $fileType = null;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Backend\Block\Template\Context $context,
        /** @delegation off */
        array $data = []
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context, $data);
    }
    /**
     * @return \Magento\Framework\Phrase|string|\Wyomind\DataFeedManager\Model\Feeds
     */
    public function getContent()
    {
        $request = $this->getRequest();
        $id = $request->getParam('id');
        $model = $this->_dfmModel;
        $model->limit = $this->_framework->getDefaultConfig('datafeedmanager/system/preview');
        $model->setDisplay(true);
        $model->load($id);
        try {
            $content = $model->generateFile($request);
            $this->fileType = $model->getType() == 1 ? "xml" : "other";
            return $content;
        } catch (\Exception $e) {
            return __('Unable to generate the data feed : ' . nl2br($e->getMessage()));
        }
    }
}