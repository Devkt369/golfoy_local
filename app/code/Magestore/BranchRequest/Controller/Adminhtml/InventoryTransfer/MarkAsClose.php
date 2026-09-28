<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;

/**
 * Class BranchRequest MarkAsClose
 *
 * @SuppressWarnings(PHPMD)
 */
class MarkAsClose extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_BranchRequest::closed';

    /**
     * MarkAsClose execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $transferId = $this->getRequest()->getParam('id');

        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $transferStock */
        $transferStock = $this->inventoryTransferFactory->create()->load($transferId);

        if (!$transferStock->getInventorytransferId()) {
            $this->messageManager->addErrorMessage(__('Could not found branch request with ID %1', $transferId));
            return $resultRedirect->setPath('*/*/edit', ['id' => $transferId]);
        }

        $transferStock->setStatus(InventoryTransferInterface::STATUS_CLOSED)->save();
        $this->messageManager->addSuccessMessage(
            __('The branch request %1 has been closed.', $transferStock->getBranchrequestCode())
        );

        return $resultRedirect->setPath('*/*/edit', ['id' => $transferId]);
    }
}
