<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\AbstractInventoryTransfer;

/**
 * Class BranchRequest Receive Button
 */
class Receive extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('Magestore_BranchRequest::receive')) {
            return [];
        }

        $data = [];
        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->getInventoryTransfer();
        if ($inventoryTransfer->canReceive()) {
            $targetName = 'os_branchrequest_edit_form.areas.transfer_summary.transfer_summary.receive_modal';
            $data = [
                'label' => __('Receive'),
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
