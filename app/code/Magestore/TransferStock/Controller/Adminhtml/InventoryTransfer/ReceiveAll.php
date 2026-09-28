<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\TransferStock\Controller\Adminhtml\InventoryTransfer;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magestore\TransferStock\Controller\Adminhtml\InventoryTransfer\InventoryTransfer;

/**
 * Class Index
 */
class ReceiveAll extends InventoryTransfer implements HttpGetActionInterface
{
    /**
     * History action.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $transferId = $this->getRequest()->getParam('id');

        try {
            if ($this->transferManagement->receiveAllProducts($transferId)) {
                $this->messageManager->addSuccessMessage(__('Receiving was created successfully!'));
            } else {
                $this->messageManager->addErrorMessage(__('Cannot receive all product in this inventory transfer.'));
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/edit', ['id' => $transferId]);
    }
}
