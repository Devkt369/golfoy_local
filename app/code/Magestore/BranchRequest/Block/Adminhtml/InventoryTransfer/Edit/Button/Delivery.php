<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\AbstractInventoryTransfer;

/**
 * Class BranchRequest Delivery Button
 */
class Delivery extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('Magestore_BranchRequest::delivery')) {
            return [];
        }

        $data = [];
        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->getInventoryTransfer();
        if ($inventoryTransfer->canDelivery()) {
            $targetName = 'os_branchrequest_edit_form.areas.transfer_summary.transfer_summary.delivery_modal';
            $data = [
                'label' => __('Delivery'),
                'class' => 'save primary',
                'on_click' => '',
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => $targetName,
                                    'actionName' => 'openModal',
                                ]
                            ]
                        ],
                    ],
                ],
                'sort_order' => 80,
            ];
        }

        return $data;
    }
}
