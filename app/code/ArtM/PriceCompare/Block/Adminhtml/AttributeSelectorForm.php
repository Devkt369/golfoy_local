<?php
namespace ArtM\PriceCompare\Block\Adminhtml;

use Magento\Backend\Block\Template;

class AttributeSelectorForm extends Template
{
    protected function _prepareLayout()
    {
        $this->setTemplate('ArtM_PriceCompare::attribute_selector_form.phtml');
        return parent::_prepareLayout();
    }
}