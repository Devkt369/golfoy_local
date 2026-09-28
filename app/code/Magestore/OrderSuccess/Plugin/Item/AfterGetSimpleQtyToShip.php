<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\OrderSuccess\Plugin\Item;

use Magestore\DropshipSuccess\Api\Data\DropshipShipmentInterface;
use Magestore\OrderSuccess\Api\DataProvider\IsGettingStateForOrderInterface;

/**
 * Class after action getSimpleQtyToShip
 */
class AfterGetSimpleQtyToShip
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var IsGettingStateForOrderInterface
     */
    protected $isGettingStateForOrder;

    /**
     * AfterGetSimpleQtyToShip constructor.
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\RequestInterface $request
     * @param IsGettingStateForOrderInterface $isGettingStateForOrder
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\RequestInterface $request,
        IsGettingStateForOrderInterface $isGettingStateForOrder
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->request = $request;
        $this->isGettingStateForOrder = $isGettingStateForOrder;
    }

    /**
     * After get simple qty to ship
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return mixed
     */
    public function afterGetSimpleQtyToShip(\Magento\Sales\Model\Order\Item $item)
    {
        $qty = $item->getQtyOrdered() - $item->getQtyShipped() - $item->getQtyRefunded()
            - $item->getQtyCanceled();
        if (!$this->isGettingStateForOrder->getIsGettingStateForOrder()) {
            $qty -= $item->getQtyBackordered();
        }
        if ($this->needCalculateQtyPrepareShip()) {
            $qty -= $item->getQtyPrepareship();
        }
        return max($qty, 0);
    }

    /**
     * Retrieve item qty available for cancel
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float|integer
     */
    public function afterGetQtyToCancel(\Magento\Sales\Model\Order\Item $item)
    {
        $qtyToShip = $item->getQtyToShip();
        $qtyToShip += $item->getQtyBackordered();
        if ($this->needCalculateQtyPrepareShip()) {
            $qtyToShip += $item->getQtyPrepareship();
        }
        $qtyToCancel = min($item->getQtyToInvoice(), $qtyToShip);
        return max($qtyToCancel, 0);
    }

    /**
     * Get need calculate qty prepare ship
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return float|integer
     */
    public function needCalculateQtyPrepareShip()
    {
        $moduleName = $this->request->getModuleName();
        if (!$this->coreRegistry->registry(DropshipShipmentInterface::CREATE_SHIPMENT_BY_DROPSHIP) &&
            ($moduleName != 'fulfilsuccess' && $moduleName != 'dropship')) {
            return true;
        }
        return false;
    }
}
