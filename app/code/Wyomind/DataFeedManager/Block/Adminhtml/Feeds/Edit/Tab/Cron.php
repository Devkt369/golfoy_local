<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab;

/**
 * Class Cron
 * @package Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab
 */
class Cron extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @return \Magento\Backend\Block\Widget\Form\Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('data_feed');

        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');

        $form->setValues($model->getData());
        $this->setForm($form);

        $this->setTemplate('edit/cron.phtml');

        return parent::_prepareForm();
    }

    /**
     * @return mixed
     */
    public function getDFMCronExpr()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getCronExpr();
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('Cron schedule');
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('Cron schedule');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
