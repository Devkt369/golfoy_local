<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\TransferStock\Controller\Adminhtml\InventoryTransfer;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Class GetProductJsonByBarcode
 *
 * Get product json by barcode string
 */
class GetProductJsonByBarcode extends InventoryTransfer implements HttpPostActionInterface
{
    /**
     * Get products data by barcode
     *
     * @return ResponseInterface|ResultInterface|void
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $this->getResponse()->representJson(
            $this->transferManagement->getProductJsonByBarcode((string) $this->getRequest()->getParam('barcode'))
        );
    }
}
