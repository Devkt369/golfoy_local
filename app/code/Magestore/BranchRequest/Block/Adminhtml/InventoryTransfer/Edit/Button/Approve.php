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
 * Class BranchRequest Approve
 */
class Approve extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('Magestore_BranchRequest::approve')) {
            return [];
        }

        if ($this->request->getParam('id')
            && $this->getInventoryTransfer()->getStatus() == InventoryTransferInterface::STATUS_WAIT_APPROVE) {
            $url = $this->getUrl('*/*/approve', ['_secure' => true, 'id' => $this->request->getParam('id')]);
            $message = __('Are you sure you want to Approve this Branch Request?');
            return [
                'label' => __('Approve'),
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    $message,
                    $url
                ),
                'class' => 'save primary',
                'sort_order' => 20
            ];
        }
        return [];
    }
}
