<?php

namespace ArtM\CustomerSurvey\Block\Adminhtml\Location\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Back extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('customersurvey/location/index')),
            'class' => 'back',
            'sort_order' => 10,
        ];
    }
}
