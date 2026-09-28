<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer;

use Magento\Framework\Controller\ResultFactory;

/**
 * History Grid Holiday Action.
 *
 * @SuppressWarnings(PHPMD)
 */
class Import extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer
{
    /**
     * Import execute
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            try {
                /** @var \Magestore\BranchRequest\Model\InventoryTransfer\CsvImportHandler $importHandler */
                $importHandler = $this->_objectManager->create(
                    \Magestore\BranchRequest\Model\InventoryTransfer\CsvImportHandler::class
                );
                $result = $importHandler->importFromCsvFile($this->getRequest()->getFiles('import_product'));
                if ($result['status']) {
                    $this->messageManager->addSuccessMessage(__($result['message']));
                } else {
                    $this->messageManager->addErrorMessage(__($result['message']));
                }

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Invalid file upload attempt'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Invalid file upload attempt'));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
        return $resultRedirect;
    }
}
