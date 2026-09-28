<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab;

/**
 * Class Ftp
 * @package Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab
 */
class Ftp extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    public $_dfmHelper = null;
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
     * @return \Magento\Backend\Block\Widget\Form\Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        /* @var $model \Excercise\Weblog\Model\Blogpost */
        $model = $this->_coreRegistry->registry('data_feed');
        /** @var \Magento\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('');
        $fieldset = $form->addFieldset('ftp_fieldset', ['legend' => __('Ftp settings')]);
        $fieldset->addField('ftp_enabled', 'select', ['label' => __('Enable FTP upload'), 'name' => 'ftp_enabled', 'id' => 'ftp_enabled', 'required' => true, 'values' => $this->_dfmHelper->getYesNoOptions()]);
        $fieldset->addField('use_sftp', 'select', ['label' => __('Use SFTP'), 'name' => 'use_sftp', 'id' => 'use_sftp', 'required' => true, 'values' => $this->_dfmHelper->getYesNoOptions()]);
        $fieldset->addField('ftp_ssl', 'select', ['label' => __('Use SSL'), 'name' => 'ftp_ssl', 'id' => 'ftp_ssl', 'required' => true, 'values' => $this->_dfmHelper->getYesNoOptions()]);
        $fieldset->addField('ftp_active', 'select', ['label' => __('Use active mode'), 'name' => 'ftp_active', 'id' => 'ftp_active', 'required' => true, 'values' => $this->_dfmHelper->getYesNoOptions()]);
        $fieldset->addField('ftp_host', 'text', ['label' => __('Host'), 'name' => 'ftp_host', 'id' => 'ftp_host']);
        $fieldset->addField('ftp_port', 'text', ['label' => __('Port'), 'name' => 'ftp_port', 'id' => 'ftp_port']);
        $fieldset->addField('ftp_login', 'text', ['label' => __('Login'), 'name' => 'ftp_login', 'id' => 'ftp_login']);
        $fieldset->addField('ftp_password', 'password', ['label' => __('Password'), 'name' => 'ftp_password', 'id' => 'ftp_password']);
        $fieldset->addField('ftp_dir', 'text', ['label' => __('Destination directory'), 'name' => 'ftp_dir', 'id' => 'ftp_dir', 'note' => "<a style='margin:10px; display:block;' href='javascript:void(require([\"dfm_ftp\"], function (ftp) {ftp.test(\"" . $this->getUrl('*/*/ftp') . "\"); }));'>Test Connection</a>"]);
        $this->setChild('form_after', $this->getLayout()->createBlock('Magento\\Backend\\Block\\Widget\\Form\\Element\\Dependence')->addFieldMap('ftp_enabled', 'ftp_enabled')->addFieldMap('use_sftp', 'use_sftp')->addFieldMap('ftp_host', 'ftp_host')->addFieldMap('ftp_login', 'ftp_login')->addFieldMap('ftp_password', 'ftp_password')->addFieldMap('ftp_dir', 'ftp_dir')->addFieldMap('ftp_ssl', 'ftp_ssl')->addFieldMap('ftp_active', 'ftp_active')->addFieldMap('ftp_port', 'ftp_port')->addFieldDependence('ftp_host', 'ftp_enabled', 1)->addFieldDependence('ftp_port', 'ftp_enabled', 1)->addFieldDependence('use_sftp', 'ftp_enabled', 1)->addFieldDependence('ftp_login', 'ftp_enabled', 1)->addFieldDependence('ftp_password', 'ftp_enabled', 1)->addFieldDependence('ftp_active', 'ftp_enabled', 1)->addFieldDependence('ftp_active', 'use_sftp', 0)->addFieldDependence('ftp_dir', 'ftp_enabled', 1)->addFieldDependence('ftp_ssl', 'use_sftp', 0)->addFieldDependence('ftp_ssl', 'ftp_enabled', 1));
        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('Ftp settings');
    }
    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('Ftp settings');
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