<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\Receive;

/**
 * Class BranchRequest View
 *
 * @SuppressWarnings(PHPMD)
 */
class View extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer
{
    /**
     * View execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id', null);

        /** @var \Magestore\BranchRequest\Model\InventoryTransfer\Receive $receive */
        $receive = $this->_receiveFactory->create();
        $resultPage = $this->_resultPageFactory->create();
        if ($id) {
            $this->_receiveResource->load($receive, $id);
            if (!$receive->getReceiveId()) {
                $this->messageManager->addErrorMessage(__('This receiving no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('current_receive_view', $receive);
        $this->_session->setData('current_receive_id', $receive->getReceiveId());
        $resultPage->getConfig()->getTitle()->prepend(__('View Receipt Note #%1', $receive->getReceiveCode()));

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
