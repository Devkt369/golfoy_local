<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Controller\Adminhtml\OrdersPotentialError;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magestore\InventoryMovement\Service\PotentialErrorOrders\PotentialErrorOrdersService;
use Psr\Log\LoggerInterface;

/**
 * Class Refresh
 *
 * Use for orders potential error list action
 */
class Refresh extends Action implements HttpGetActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_InventoryMovementAdminUi::orders_potential_error';

    /**
     * @var PotentialErrorOrdersService
     */
    protected $potentialErrorOrdersService;

    /**
     * Refresh constructor.
     * @param Context $context
     * @param PotentialErrorOrdersService $potentialErrorOrdersService
     */
    public function __construct(
        Context $context,
        PotentialErrorOrdersService $potentialErrorOrdersService
    ) {
        parent::__construct($context);
        $this->potentialErrorOrdersService = $potentialErrorOrdersService;
    }

    /**
     * Update orders potential error report
     *
     * @return ResultRedirect
     */
    public function execute()
    {
        /** @var ResultRedirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $this->potentialErrorOrdersService->aggregate();
            $this->messageManager->addSuccessMessage(__('You updated the report.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('We can\'t update the report.'));
            $this->_objectManager->get(LoggerInterface::class)->critical($e);
        }

        return $resultRedirect->setPath('inventorymovement/orderspotentialerror/');
    }
}
