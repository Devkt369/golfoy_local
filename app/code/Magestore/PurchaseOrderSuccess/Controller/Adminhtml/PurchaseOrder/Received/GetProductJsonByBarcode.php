<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\PurchaseOrderSuccess\Controller\Adminhtml\PurchaseOrder\Received;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerialize;
use Magestore\PurchaseOrderSuccess\Controller\Adminhtml\AbstractAction;
use Magestore\PurchaseOrderSuccess\Ui\DataProvider\PurchaseOrder\ReceivedProduct\Form\Modifier\ProductList;

/**
 * Class GetProductJsonByBarcode
 *
 * Get product json by barcode controller
 */
class GetProductJsonByBarcode extends AbstractAction implements
    HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magestore_PurchaseOrderSuccess::received_purchase_order';

    /**
     * Execute the controller
     *
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $barcode = (string) $this->getRequest()->getParam('barcode');
        $result = [];
        /* @var ProductList $productListReceived */
        $productListReceived = $this->_objectManager->get(ProductList::class);
        $collection = $productListReceived->getReceivedProductBarcodeCollection();
        $collection->getSelect()->where(
            $collection->getConnection()->prepareSqlCondition('barcode', ['finset' => $barcode])
        );
        $collection->setPageSize(1)->setCurPage(1);
        foreach ($collection->getItems() as $item) {
            $result[$item->getBarcode()] = $item->getData();
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get(JsonSerialize::class)->serialize($result)
        );
    }
}
