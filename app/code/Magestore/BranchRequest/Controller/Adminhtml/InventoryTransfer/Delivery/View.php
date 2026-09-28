<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\Delivery;

/**
 * Class BranchRequest View
 *
 * @SuppressWarnings(PHPMD)
 */
class View extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer
{
    /**
     * @var \Magestore\BranchRequest\Api\Data\InventoryTransfer\DeliveryInterfaceFactory
     */
    protected $_deliveryFactory;

    /**
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Delivery
     */
    protected $_deliveryResource;

    /**
     * View constructor.
     *
     * @param \Magestore\BranchRequest\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Magestore\BranchRequest\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->_deliveryFactory = $context->getDeliveryFactory();
        $this->_deliveryResource = $context->getDeliveryResource();
    }

    /**
     * View execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id', null);

        /** @var \Magestore\BranchRequest\Model\InventoryTransfer\Delivery $delivery */
        $delivery = $this->_deliveryFactory->create();
        $resultPage = $this->_resultPageFactory->create();
        if ($id) {
            $this->_deliveryResource->load($delivery, $id);
            if (!$delivery->getDeliveryId()) {
                $this->messageManager->addErrorMessage(__('This delivery no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('current_delivery_view', $delivery);
        $this->_session->setData('current_delivery_id', $delivery->getDeliveryId());
        $resultPage->getConfig()->getTitle()->prepend(__('View Delivery Note #%1', $delivery->getDeliveryCode()));

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
