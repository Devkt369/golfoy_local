<?php
namespace ArtM\RecommendedFilter\Block\Adminhtml\Category\Tab;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;

class Editroduct
{
    public function afterSetForm(\Magento\Catalog\Block\Adminhtml\Category\Tab\Product $subject, $result)
    {
        $fieldset = $subject->getForm()->getElement('base_fieldset');
        $fieldset->addField(
            'sorting_points',
            'text',
            [
                'name' => 'sorting_points',
                'label' => __('Sorting Points'),
                'title' => __('Sorting Points'),
                'required' => false
            ]
        );

        return $result;
    }
}