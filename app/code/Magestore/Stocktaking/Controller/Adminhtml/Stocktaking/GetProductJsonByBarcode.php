<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types = 1);

namespace Magestore\Stocktaking\Controller\Adminhtml\Stocktaking;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magestore\Stocktaking\Api\StocktakingManagementInterface;

/**
 * Get product json by barcode string
 */
class GetProductJsonByBarcode extends Action implements HttpPostActionInterface
{
    /**
     * @var StocktakingManagementInterface
     */
    protected $stocktakingManagement;

    /**
     * GetBarcodeJson constructor.
     *
     * @param Context $context
     * @param StocktakingManagementInterface $stocktakingManagement
     */
    public function __construct(
        Context $context,
        StocktakingManagementInterface $stocktakingManagement
    ) {
        parent::__construct($context);
        $this->stocktakingManagement = $stocktakingManagement;
    }

    /**
     * Execute the controller to get json barcode
     *
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $this->getResponse()->representJson(
            $this->stocktakingManagement->getProductJsonByBarcode((string) $this->getRequest()->getParam('barcode'))
        );
    }
}
