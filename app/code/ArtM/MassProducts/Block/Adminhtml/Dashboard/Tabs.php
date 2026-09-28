<?php
namespace ArtM\MassProducts\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Widget\Tabs as BackendTabs;

class Tabs extends BackendTabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('massproducts_dashboard_tabs');
        $this->setTemplate('Magento_Backend::widget/tabshoriz.phtml');
        $this->setDestElementId('massproducts-dashboard-tab-content');
        $this->setTitle(__('Mass Upload Product Tabs'));
    }

    protected function _beforeToHtml()
    {
        $tabs = [
            'products_upload' => __('Products Upload'),
            'configuration' => __('Configuration'),
        ];

        $activeTab = (string)$this->getRequest()->getParam('active_tab', 'products_upload');
        if (!isset($tabs[$activeTab])) {
            $activeTab = 'products_upload';
        }
        $this->setActiveTab($activeTab);

        foreach ($tabs as $code => $label) {
            $this->addTab(
                $code,
                [
                    'label' => $label,
                    'title' => $label,
                    'url' => $this->getUrl('massproducts/index/tab', ['tab' => $code]),
                    'class' => 'ajax',
                ]
            );
        }

        return parent::_beforeToHtml();
    }
}
