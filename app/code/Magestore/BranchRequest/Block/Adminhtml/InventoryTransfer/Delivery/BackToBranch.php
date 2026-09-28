<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Delivery;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\AbstractInventoryTransfer;

/**
 * Class BranchRequest BackToBranch
 */
class BackToBranch extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        if ($this->request->getParam('id')) {
            /** @var \Magestore\BranchRequest\Model\InventoryTransfer\Delivery $delivery */
            $delivery = $this->registry->registry('current_delivery_view');
            if ($delivery && $delivery->getInventorytransferId()) {
                $branchRequestId = $delivery->getInventorytransferId();
                $url = $this->getUrl('branchrequest/inventorytransfer/edit', ['id' => $branchRequestId]);
                return [
                    'label' => __('Back to Branch Request'),
                    'on_click' => sprintf("location.href = '%s';", $url),
                    'class' => 'save',
                    'sort_order' => 20
                ];
            }
        }
        return [];
    }
}
