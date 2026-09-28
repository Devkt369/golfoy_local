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
 * Class BranchRequest MarkAsClose
 */
class MarkAsClose extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('Magestore_BranchRequest::closed')) {
            return [];
        }

        if ($this->request->getParam('id')
            && $this->getInventoryTransfer()->getStatus() == InventoryTransferInterface::STATUS_PROCESSING) {
            $url = $this->getUrl('*/*/markAsClose', ['_secure' => true, 'id' => $this->request->getParam('id')]);
            $message = __("Are you sure you want to close this request? You can\'t deliver or receive items anymore.");
            return [
                'label' => __('Mark as Closed'),
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    $message,
                    $url
                ),
                'class' => 'delete',
                'sort_order' => 20
            ];
        }
        return [];
    }
}
