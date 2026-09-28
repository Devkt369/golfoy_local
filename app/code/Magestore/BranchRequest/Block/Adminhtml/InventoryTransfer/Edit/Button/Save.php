<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\AbstractInventoryTransfer;

/**
 * Class BranchRequest SaveButton
 */
class Save extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array|void
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('Magestore_BranchRequest::addnew')) {
            return [];
        }

        if (!$this->request->getParam('id')) {
            return [
                'label' => __('Prepare Product List'),
                'class' => 'save primary',
                'on_click' => '',
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => 'os_branchrequest_form.os_branchrequest_form',
                                    'actionName' => 'save',
                                    'params' => [
                                        true
                                    ]
                                ]
                            ]
                        ]
                    ],
                ],
                'sort_order' => 40,
            ];
        }
    }
}
