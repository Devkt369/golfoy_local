<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block\Adminhtml\Group\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $groupId = $this->getGroupId();
        if (!$groupId) {
            return [];
        }

        return [
            'label' => __('Delete Slider Group'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to delete this slider group?') . '\', \''
                . $this->getUrl('*/*/delete', ['group_id' => $groupId]) . '\')',
            'sort_order' => 20,
        ];
    }
}
