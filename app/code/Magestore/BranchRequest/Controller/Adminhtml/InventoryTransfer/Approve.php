<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;

/**
 * Class BranchRequest Approve
 * @SuppressWarnings(PHPMD)
 */
class Approve extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_BranchRequest::approve';

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
            $this->messageManager->addErrorMessage(__('Could not found transfer with ID %1', $transferId));
            return $resultRedirect->setPath('*/*/edit', ['id' => $transferId]);
        }
    
        $curUser = $this->authSession->getUser();
        $transferStock->setStatus(InventoryTransferInterface::STATUS_APPROVED)
                      ->setApprovedById($curUser->getId())
                      ->setApprovedByName($curUser->getUserName())
                      ->save();
        $this->messageManager->addSuccessMessage(
            __('The transfer %1 has been approved.', $transferStock->getBranchrequestCode())
        );

        return $resultRedirect->setPath('*/*/edit', ['id' => $transferId]);
    }
}
