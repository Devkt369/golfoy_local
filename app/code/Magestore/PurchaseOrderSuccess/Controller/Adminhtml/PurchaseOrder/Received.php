<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PurchaseOrderSuccess\Controller\Adminhtml\PurchaseOrder;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magestore\PurchaseOrderSuccess\Controller\Adminhtml\AbstractAction;

/**
 * PurchaseOrderSuccess - Received
 */
class Received extends AbstractAction implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magestore_PurchaseOrderSuccess::received_purchase_order';

    /**
     * @var \Magestore\PurchaseOrderSuccess\Service\PurchaseOrder\PurchaseOrderService
     */
    protected $purchaseOrderService;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param \Magestore\PurchaseOrderSuccess\Controller\Adminhtml\Context $context
     * @param \Magestore\PurchaseOrderSuccess\Service\PurchaseOrder\PurchaseOrderService $purchaseOrderService
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magestore\PurchaseOrderSuccess\Controller\Adminhtml\Context $context,
        \Magestore\PurchaseOrderSuccess\Service\PurchaseOrder\PurchaseOrderService $purchaseOrderService,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->purchaseOrderService = $purchaseOrderService;
        $this->timezone = $timezone;
    }

    /**
     * View purchase order form
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $purchaseId = $this->getRequest()->getParam('purchase_id');
        $isAll = $this->getRequest()->getParam('all');
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$purchaseId) {
            $this->messageManager->addErrorMessage(__('Please select a purchase order to received product'));
            return $resultRedirect->setPath('*/*/');
        }
        if ($isAll == 'true') {
            try {
                $receivedTime = date('Y-m-d', $this->timezone->scopeTimeStamp());
                $user = $this->_auth->getUser();
                $this->purchaseOrderService->receiveAllProduct($purchaseId, $receivedTime, $user->getUserName());
                $this->messageManager->addSuccessMessage(__('Receive all products successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $resultRedirect->setPath('*/*/view', ['id' => $purchaseId]);
        }
        return $resultRedirect->setPath('*/*/view', ['id' => $purchaseId]);
    }
}
