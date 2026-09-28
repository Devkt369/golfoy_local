<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\OrderSuccess\Controller\Adminhtml\ShipProcess;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magestore\OrderSuccess\Api\Data\ShippingChanelInterface;
use Magestore\OrderSuccess\Block\Adminhtml\Order\Grid as ItemToPickGrid;
use Magestore\OrderSuccess\Service\ShipService;

/**
 * Class Save
 *
 * Save ship process
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magestore_OrderSuccess::prepare_ship';

    /**
     * @var ShipService
     */
    protected $shipService;

    /**
     * @var ShippingChanelInterface
     */
    protected $shippingChanelInterface;

    /**
     * @var ItemToPickGrid
     */
    protected $itemToPickGrid;

    /**
     * Save constructor.
     *
     * @param Action\Context $context
     * @param ShippingChanelInterface $shippingChanelInterface
     * @param ShipService $shipService
     * @param ItemToPickGrid|null $itemToPickGrid
     */
    public function __construct(
        Action\Context $context,
        ShippingChanelInterface $shippingChanelInterface,
        ShipService $shipService,
        ItemToPickGrid $itemToPickGrid = null
    ) {
        $this->shipService = $shipService;
        $this->shippingChanelInterface = $shippingChanelInterface;
        $this->itemToPickGrid = $itemToPickGrid ?: ObjectManager::getInstance()
            ->get(ItemToPickGrid::class);
        parent::__construct($context);
    }

    /**
     * Return grid with shipping items for Ajax request
     *
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $responseAjax = new DataObject();
        $data = $this->getRequest()->getParams();
        if (isset($data['packages'])) {
            try {
                $this->validateRequestQty($data);
                $this->shipService->processShipment($data);
                $responseAjax->setMessage(__('The request has been created'));
                $responseAjax->setOk(true);
                $this->messageManager
                    ->addSuccessMessage(__('The request has been created'));
            } catch (Exception $e) {
                $responseAjax->setError(true);
                $responseAjax->setMessage($e->getMessage());
            }
        } else {
            $this->messageManager
                ->addWarningMessage(__('Can not create the ship request'));
        }
        $this->getResponse()->representJson($responseAjax->toJson());
    }

    /**
     * Validate request qty (for case order item grid can Updated before data submit)
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    private function validateRequestQty(array $data)
    {
        $qtyNeedToShip = [];
        /* Get item to pick grid to re-get current qty to ship */
        $collectionItem = $this->itemToPickGrid->getCollection();
        foreach ($collectionItem as $item) {
            $qtyNeedToShip[(int)$item->getOrderItemId()] = (float)$item->getQty();
        }
        /* End */

        /* Get total qty request of order item */
        $qtyRequest = [];
        $packages = $data['packages'];
        foreach ($packages as $package) {
            $items = $package['items'];
            foreach ($items as $item) {
                $itemId = (int)$item['order_item_id'];
                $itemRequest = (float)$item['qty'];
                if (!isset($qtyRequest[$itemId])) {
                    $qtyRequest[$itemId] = $itemRequest;
                } else {
                    $qtyRequest[$itemId] += $itemRequest;
                }
            }
        }
        /* End */

        /* Validate if qty request > qty to ship */
        foreach ($qtyRequest as $itemId => $qty) {
            if (!isset($qtyNeedToShip[$itemId])
                || (isset($qtyNeedToShip[$itemId]) && $qty > $qtyNeedToShip[$itemId])) {
                throw new LocalizedException(
                    __('This request is invalid. Please reload page and retry again!')
                );
            }
        }
        /* End */
    }
}
