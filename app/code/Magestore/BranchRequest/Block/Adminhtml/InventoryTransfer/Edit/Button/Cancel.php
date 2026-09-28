<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferProductInterface;
use Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\AbstractInventoryTransfer;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\Collection;

/**
 * Class BranchRequest Cancel
 */
class Cancel extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('Magestore_BranchRequest::cancel')) {
            return [];
        }

        if ($this->canCancel()) {
            $url = $this->getUrl('*/*/cancel', ['_secure' => true, 'id' => $this->request->getParam('id')]);
            $message = __('Are you sure you want to Cancel this Branch Request?');
            return [
                'label' => __('Cancel'),
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    $message,
                    $url
                ),
                'class' => 'delete',
                'sort_order' => 12
            ];
        }

        return [];
    }

    /**
     * CanCancel
     *
     * @return bool
     */
    public function canCancel()
    {
        $isCanCancel = false;
        if ($this->request->getParam('id')) {
            $statusCanCencel = [
                InventoryTransferInterface::STATUS_NEW,
                InventoryTransferInterface::STATUS_WAIT_APPROVE,
            ];
            if (in_array($this->getInventoryTransfer()->getStatus(), $statusCanCencel)) {
                $isCanCancel = true;
            }
            if ($this->getInventoryTransfer()->getStatus() == InventoryTransferInterface::STATUS_APPROVED) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                /** @var Collection $collection */
                $collection = $objectManager->create(
                    Collection::class
                );
                $collection->addFieldToFilter(
                    InventoryTransferProductInterface::INVENTORYTRANSFER_ID,
                    $this->request->getParam('id')
                );
                $collection->addFieldToFilter('qty_delivered', ['gt' => 0]);
                if (!$collection->getSize()) {
                    $isCanCancel = true;
                }
            }
        }

        return $isCanCancel;
    }
}
