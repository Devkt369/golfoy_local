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
 * Class BranchRequest Update
 */
class Update extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('Magestore_BranchRequest::save')) {
            return [];
        }

        $data = [];
        $inventoryTransfer = $this->getInventoryTransfer();
        if ($this->request->getParam('id')
            && $inventoryTransfer->getStatus() != InventoryTransferInterface::STATUS_CANCELED
            && $inventoryTransfer->getStatus() != InventoryTransferInterface::STATUS_CLOSED) {
            $data = [
                'label' => __('Save'),
                'class' => 'save primary',
                'on_click' => '',
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => 'os_branchrequest_edit_form.areas',
                                    'actionName' => 'save',
                                    'params' => [
                                        true,
                                        [
                                            'action' => "update"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                ],
                'sort_order' => 90,
            ];
        }
        
        return $data;
    }
}
