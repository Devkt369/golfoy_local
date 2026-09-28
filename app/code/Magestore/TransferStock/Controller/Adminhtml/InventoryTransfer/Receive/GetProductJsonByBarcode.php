<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\TransferStock\Controller\Adminhtml\InventoryTransfer\Receive;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magestore\TransferStock\Controller\Adminhtml\InventoryTransfer\InventoryTransfer;

/**
 * Class GetProductJsonByBarcode
 *
 * Get product json by barcode string
 */
class GetProductJsonByBarcode extends InventoryTransfer implements HttpPostActionInterface
{
    /**
     * Execute controller to get result
     *
     * @return \Magento\Framework\App\ResponseInterface|ResultInterface|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $transferId = (int) $this->_request->getParam('transfer_id');
        $barcode = (string) $this->_request->getParam('barcode');
        $this->getResponse()->representJson(
            $this->transferManagement->getProductReceivingJsonByBarcode($transferId, $barcode)
        );
    }
}
