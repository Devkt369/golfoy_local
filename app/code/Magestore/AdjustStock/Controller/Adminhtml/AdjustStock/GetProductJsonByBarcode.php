<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\AdjustStock\Controller\Adminhtml\AdjustStock;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class GetProductJsonByBarcode
 *
 * Controller to get product json by barcode
 */
class GetProductJsonByBarcode extends AdjustStock implements HttpPostActionInterface
{
    /**
     * Get product json by barcode string
     *
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $this->getResponse()->representJson(
            $this->adjustStockManagement->getProductJsonByBarcode(
                (string) $this->getRequest()->getParam('barcode')
            )
        );
    }
}
