<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer;

/**
 * Class BranchRequest Index
 *
 * @SuppressWarnings(PHPMD)
 */
class Index extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer
{
    /**
     * History action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Magestore_BranchRequest::manage');
        $resultPage->getConfig()->getTitle()->prepend(__('Branch Requests'));
        $resultPage->addBreadcrumb(__('Branch Requests'), __('Branch Requests'));

        return $resultPage;
    }
}
