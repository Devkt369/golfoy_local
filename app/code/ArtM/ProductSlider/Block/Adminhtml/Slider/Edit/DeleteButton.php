<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block\Adminhtml\Slider\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $sliderId = $this->getSliderId();
        if (!$sliderId) {
            return [];
        }

        return [
            'label' => __('Delete Slider'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to delete this slider?') . '\', \''
                . $this->getUrl('*/*/delete', ['slider_id' => $sliderId]) . '\')',
            'sort_order' => 20,
        ];
    }
}
