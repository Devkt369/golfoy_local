<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\FulfilSuccess\Service\PickRequest;

use Magento\Backend\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Item;
use Magestore\FulfilSuccess\Api\Data\PickRequestInterface;
use Magestore\FulfilSuccess\Api\Data\PickRequestItemInterface;
use Magestore\FulfilSuccess\Api\OrderItemRepositoryInterface;
use Magestore\FulfilSuccess\Api\PickRequestItemRepositoryInterface;
use Magestore\FulfilSuccess\Api\PickRequestRepositoryInterface;
use Magestore\FulfilSuccess\Helper\Data;
use Magestore\FulfilSuccess\Service\Locator\UserServiceInterface;

/**
 * PickRequest - PickService
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PickService
{
    const PICKING_REQUEST_SESSION_ID = 'picking_request_session_id';

    /**
     * @var PickRequestRepositoryInterface
     */
    protected $pickRequestRepository;

    /**
     * @var PickRequestItemRepositoryInterface
     */
    protected $pickRequestItemRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var ItemService
     */
    protected $itemService;

    /**
     * @var PickRequestService
     */
    protected $pickRequestService;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Data
     */
    protected $helper;
    
    /**
     * @var UserServiceInterface
     */
    protected $userService;

    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * @param PickRequestRepositoryInterface $pickRequestRepository
     * @param PickRequestItemRepositoryInterface $pickRequestItemRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ItemService $itemService
     * @param PickRequestService $pickRequestService
     * @param UserServiceInterface $userService
     * @param Data $helper
     * @param Session $session
     */
    public function __construct(
        PickRequestRepositoryInterface $pickRequestRepository,
        PickRequestItemRepositoryInterface $pickRequestItemRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ItemService $itemService,
        PickRequestService $pickRequestService,
        UserServiceInterface $userService,
        Data $helper,
        Session $session
    ) {
        $this->pickRequestRepository = $pickRequestRepository;
        $this->pickRequestItemRepository = $pickRequestItemRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->itemService = $itemService;
        $this->pickRequestService = $pickRequestService;
        $this->userService = $userService;
        $this->helper = $helper;
        $this->session = $session;
        $this->_objectManager = ObjectManager::getInstance();
    }

    /**
     * Finish pick request
     *
     * @param PickRequestInterface $request
     * @param array $pickedData
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function finishPickRequest($request, $pickedData)
    {
        if (!$request || !$request->getId()) {
            return;
        }
        $status = $request->getData(PickRequestInterface::STATUS);
        $qtyPicked = 0;
        if ($status !=  PickRequestInterface::STATUS_PICKING) {
            return;
        }
        if (!empty($pickedData)) {
            $items = $this->pickRequestItemRepository->getListByRequestId($request->getId());
            if (count($items) > 0) {
                foreach ($items as $item) {
                    $itemId = $item->getData(PickRequestItemInterface::PICK_REQUEST_ITEM_ID);
                    $parentId = $item->getData(PickRequestItemInterface::PARENT_PICK_REQUEST_ITEM_ID);
                    if (isset($pickedData[$itemId])) {
                        $item->setData(
                            PickRequestItemInterface::PICKED_QTY,
                            $pickedData[$itemId][PickRequestItemInterface::PICKED_QTY]
                        );
                        $this->pickRequestItemRepository->save($item);
                    } elseif (!empty($parentId) && isset($pickedData[$parentId])) {
                        $parentPickedQty = $pickedData[$parentId][PickRequestItemInterface::PICKED_QTY];
                        $parentRequestQty = $pickedData[$parentId][PickRequestItemInterface::REQUEST_QTY];
                        $pickedQty = $item->getData(PickRequestItemInterface::PICKED_QTY);
                        $requestQty = $item->getData(PickRequestItemInterface::REQUEST_QTY);
                        $newPickedQty = ($parentPickedQty == $parentRequestQty) ?
                            $requestQty:($parentPickedQty/$parentRequestQty*$requestQty);
                        if ($pickedQty != $newPickedQty) {
                            $item->setData(PickRequestItemInterface::PICKED_QTY, $newPickedQty);
                            $this->pickRequestItemRepository->save($item);
                        }
                    }
                    $itemModel = $this->_objectManager->create(Item::class)
                        ->load($item->getData(PickRequestItemInterface::ITEM_ID));

                    /* ignore ship together children of bundle*/
                    if (!(!$itemModel->isShipSeparately() && $parentId)) {
                        $qtyPicked = $qtyPicked + $item->getData(PickRequestItemInterface::PICKED_QTY);
                    }
                }
            }
        }
        $request->setTotalItems($qtyPicked);
        $request->setAge($this->pickRequestService->getAge($request));
        $request->setData(PickRequestInterface::STATUS, PickRequestInterface::STATUS_PICKED);
        $request->setUserId($this->userService->getCurrentUserId());
        $this->pickRequestRepository->save($request);

        $this->pickRequestService->moveItemsToNeedToShip($request);
        $this->pickRequestService->createPackRequest($request);
    }

    /**
     * Finish picking request
     *
     * @param array $pickedData
     * @param bool $pickRequestId
     */
    public function finishPickingRequest($pickedData, $pickRequestId = false)
    {
        $resetSession = true;
        $request = $this->session->getData(self::PICKING_REQUEST_SESSION_ID);
        if ($pickRequestId) {
            $resetSession = ($request && ($request->getId() == $pickRequestId))?true:false;
            $request = $this->pickRequestRepository->getById($pickRequestId);
        }
        $this->finishPickRequest($request, $pickedData);
        if ($resetSession) {
            $this->session->setData(self::PICKING_REQUEST_SESSION_ID, null);
        }
    }

    /**
     * Start Pick Request
     *
     * @param PickRequestInterface $request
     * @return array
     */
    public function startPickRequest($request)
    {
        $result = [];
        if ($request->getId()) {
            $this->session->setData(self::PICKING_REQUEST_SESSION_ID, $request);
            $result = $this->getPickRequestItems($request);
        }
        return $result;
    }

    /**
     * Get Pick Request Items
     *
     * @param PickRequestInterface $request
     * @return array
     */
    public function getPickRequestItems($request)
    {
        $result = [];
        if ($request->getId()) {
            $items = $this->pickRequestItemRepository->getListByRequestId($request->getId());
            if (count($items) > 0) {
                foreach ($items as $item) {
                    $data = $item->getData();
                    $data[PickRequestItemInterface::REQUEST_QTY] = (float)$data[PickRequestItemInterface::REQUEST_QTY];
                    $data[PickRequestItemInterface::PICKED_QTY] = (float)$data[PickRequestItemInterface::PICKED_QTY];
                    $data[PickRequestItemInterface::ITEM_BARCODE] = $this->getPickRequestItemBarcodes($item);
                    $result[] = $data;
                }
            }
        }
        return $result;
    }

    /**
     * Can Pick
     *
     * @param PickRequestInterface $request
     * @return boolean
     */
    public function canPick($request)
    {
        $canPick = false;
        if ($request->getId()) {
            $pickingRequest = $this->session->getData(self::PICKING_REQUEST_SESSION_ID);
            $canPick = !$pickingRequest || ($pickingRequest && ($pickingRequest->getId() == $request->getId()));
        }
        return $canPick;
    }

    /**
     * Get Picking Items
     *
     * @return array
     */
    public function getPickingItems()
    {
        $items = [];
        $pickingRequest = $this->session->getData(self::PICKING_REQUEST_SESSION_ID);
        if ($pickingRequest && $pickingRequest->getId()) {
            $items = $this->getPickRequestItems($pickingRequest);
        }
        return $items;
    }

    /**
     * Get Picking Order Increment Id
     *
     * @return string
     */
    public function getPickingOrderIncrementId()
    {
        $orderIncrementId = '';
        $pickingRequest = $this->session->getData(self::PICKING_REQUEST_SESSION_ID);
        if ($pickingRequest && $pickingRequest->getId()) {
            $orderIncrementId = $pickingRequest->getData(PickRequestInterface::ORDER_INCREMENT_ID);
        }
        return $orderIncrementId;
    }

    /**
     * Get Picking Request Id
     *
     * @return string
     */
    public function getPickingRequestId()
    {
        $requestId = '';
        $pickingRequest = $this->session->getData(self::PICKING_REQUEST_SESSION_ID);
        if ($pickingRequest && $pickingRequest->getId()) {
            $requestId = $pickingRequest->getData(PickRequestInterface::PICK_REQUEST_ID);
        }
        return $requestId;
    }

    /**
     * Get Pick Request Item Barcodes
     *
     * @param PickRequestItemInterface $item
     * @return string
     */
    public function getPickRequestItemBarcodes($item)
    {
        $barcodes = "";
        if ($item && $item->getId()) {
            $barcodes = $this->helper->getProductBarcodes($item->getData(PickRequestItemInterface::PRODUCT_ID));
        }
        return $barcodes;
    }

    /**
     * Get Item Barcodes By Product Id
     *
     * @param int $productId
     * @return string
     */
    public function getItemBarcodesByProductId($productId)
    {
        return $this->helper->getProductBarcodes($productId);
    }

    /**
     * Remove picking session data
     */
    public function removePickingSession()
    {
        $this->session->setData(self::PICKING_REQUEST_SESSION_ID, null);
    }

    /**
     * Prepare Barcodes For View
     *
     * @param string $barcodes
     * @return array|string
     */
    public function prepareBarcodesForView($barcodes)
    {
        if ($barcodes) {
            $barcodes = explode('||', (string)$barcodes);
            $barcodes = implode(', ', $barcodes);
        }
        return $barcodes;
    }
}
