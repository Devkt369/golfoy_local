<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;

/**
 * Class BranchRequest Save
 *
 * @SuppressWarnings(PHPMD)
 */
class Save extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_BranchRequest::addnew';

    /**
     * History action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        if (!isset($data[InventoryTransferInterface::ID])) {
            $data[InventoryTransferInterface::STATUS] = InventoryTransferInterface::STATUS_NEW;
            $curUser = $this->authSession->getUser();
            $data[InventoryTransferInterface::CREATED_BY_ID] = $curUser->getId();
            $data[InventoryTransferInterface::CREATED_BY_NAME] = $curUser->getUserName();
        }

        $model = $this->inventoryTransferFactory->create();
        $model->setData($data);
        try {
            $id = isset($data[InventoryTransferInterface::ID]) ? $data[InventoryTransferInterface::ID] : 0;
            if (isset($data[InventoryTransferInterface::CODE]) && $data[InventoryTransferInterface::CODE]) {
                $data[InventoryTransferInterface::CODE] = trim($data[InventoryTransferInterface::CODE]);
            }
            if (!$this->branchRequestService->validateBranchRequestId($data[InventoryTransferInterface::CODE], $id)) {
                $this->messageManager->addErrorMessage(
                    __(
                        'This code %1 has been applied for another Branch Request. Please use another code.',
                        $data[InventoryTransferInterface::CODE]
                    )
                );
                $this->_getSession()->setFormData($data);
                if (isset($data[InventoryTransferInterface::ID])) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $data[InventoryTransferInterface::ID]]);
                }
                return $resultRedirect->setPath('*/*/new');
            }
            $this->inventoryTransferResource->save($model);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }

        if ($this->getRequest()->getParam('back') == 'edit') {
            return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
        }
        return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
    }
}
