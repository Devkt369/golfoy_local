<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Block\Adminhtml\Partner\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $partnerId = $this->getPartnerId();
        if (!$partnerId) {
            return [];
        }

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s');",
                __('Are you sure you want to delete this partner?'),
                $this->getUrl('*/*/delete', ['partner_id' => $partnerId])
            ),
            'sort_order' => 20,
        ];
    }
}
