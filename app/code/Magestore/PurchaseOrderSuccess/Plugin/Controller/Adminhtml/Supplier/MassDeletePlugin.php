<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\PurchaseOrderSuccess\Plugin\Controller\Adminhtml\Supplier;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magestore\SupplierSuccess\Controller\Adminhtml\Supplier\MassDelete;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Class MassDeletePlugin
 *
 * Prevent delete supplier if have purchase order
 */
class MassDeletePlugin extends MassDelete implements HttpPostActionInterface
{
    /**
     * Plugin to prevent delete if have purchase order
     *
     * @param MassDelete $subject
     * @param callable $proceed
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function aroundExecute(
        MassDelete $subject,
        callable $proceed
    ) {
        $collection = $this->filter->getCollection($this->supplierCollectionFactory->create());
        $deleteSuccessCount = 0;
        $supplierHasPurchase = [];
        foreach ($collection as $supplier) {
            /* @var \Magestore\PurchaseOrderSuccess\Model\ResourceModel\PurchaseOrder $purchaseOrderResource */
            $purchaseOrderResource = $this->_objectManager->get(
                \Magestore\PurchaseOrderSuccess\Model\ResourceModel\PurchaseOrder::class
            );
            /* @var \Magestore\PurchaseOrderSuccess\Model\PurchaseOrder $purchaseOrder */
            $purchaseOrder = $this->_objectManager->create(
                \Magestore\PurchaseOrderSuccess\Model\PurchaseOrder::class
            );
            $purchaseOrderResource->load($purchaseOrder, $supplier->getSupplierId(), 'supplier_id');
            if (!$purchaseOrder->getId()) {
                $supplier->delete();
                $deleteSuccessCount++;
            } else {
                $supplierHasPurchase[] = $supplier->getSupplierName();
            }
        }
        if (count($supplierHasPurchase)) {
            $this->messageManager->addErrorMessage(
                __(
                    'Can not delete "%1" because they have purchase orders relate to them.'
                    .' Please set the suppliers disabled if you do not use.',
                    implode(',', $supplierHasPurchase)
                )
            );
        }

        if ($deleteSuccessCount) {
            $this->messageManager->addSuccessMessage(__('%1 record(s) have been deleted.', $deleteSuccessCount));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
