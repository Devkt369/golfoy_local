<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;

/**
 * Class BranchRequest Edit
 *
 * @SuppressWarnings(PHPMD)
 */
class Edit extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer
{
    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id', null);
        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $model */
        $model = $this->inventoryTransferFactory->create();
        $resultPage = $this->_resultPageFactory->create();
        if ($id) {
            $this->inventoryTransferResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This inventory transfer no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
            $this->_coreRegistry->register('current_inventory_transfer', $model);
            $resultPage->getConfig()->getTitle()->prepend(
                __('Branch Request "%1"', $model->getData(InventoryTransferInterface::CODE))
            );
        } else {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/new');
        }
        return $resultPage;
    }

    /**
     * Init page.
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Magestore_BranchRequest::inventorytransfer');
        return $resultPage;
    }
}
