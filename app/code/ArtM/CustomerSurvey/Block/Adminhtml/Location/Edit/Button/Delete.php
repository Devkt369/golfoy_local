<?php

namespace ArtM\CustomerSurvey\Block\Adminhtml\Location\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Delete extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData()
    {
        $data = [];
        $locationId = $this->getLocationId();
        if ($locationId) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => "deleteConfirm('"
                    . __('Are you sure you want to delete this location?')
                    . "', '"
                    . $this->getUrl('customersurvey/location/delete', ['location_id' => $locationId])
                    . "')",
                'sort_order' => 30,
            ];
        }

        return $data;
    }
}
