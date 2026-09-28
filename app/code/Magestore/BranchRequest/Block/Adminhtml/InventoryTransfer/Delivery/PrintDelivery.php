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
 * Class BranchRequest PrintDelivery
 */
class PrintDelivery extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        if ($this->request->getParam('id')) {
            $url = $this->getUrl(
                'branchrequest/inventorytransfer_delivery/printdelivery',
                ['_secure' => true, 'id' => $this->request->getParam('id')]
            );

            return [
                'label' => __('Print'),
                'on_click' => sprintf("location.href = '%s';", $url),
                'class' => 'save',
                'sort_order' => 20
            ];
        }
        return [];
    }
}
