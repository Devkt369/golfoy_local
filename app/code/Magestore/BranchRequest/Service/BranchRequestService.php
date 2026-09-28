<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Service;

use Magestore\BranchRequest\Model\InventoryTransfer;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Collection;
use Magestore\BranchRequest\Model\InventoryTransfer\Delivery;
use Magestore\BranchRequest\Model\InventoryTransfer\Receive;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Delivery\Collection as DeliveryCollection;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Receive\Collection as ReceiveCollection;

/**
 * Class BranchRequest BranchRequestService
 */
class BranchRequestService
{
    /**
     * @var Collection
     */
    public $branchRequestCollection;

    /**
     * @var DeliveryCollection
     */
    public $deliveryCollection;

    /**
     * @var ReceiveCollection
     */
    public $receiveCollection;

    /**
     * BranchRequestService construct
     *
     * @param Collection $branchRequestCollection
     * @param DeliveryCollection $deliveryCollection
     * @param ReceiveCollection $receiveCollection
     */
    public function __construct(
        Collection         $branchRequestCollection,
        DeliveryCollection $deliveryCollection,
        ReceiveCollection $receiveCollection
    ) {
        $this->branchRequestCollection = $branchRequestCollection;
        $this->deliveryCollection = $deliveryCollection;
        $this->receiveCollection = $receiveCollection;
    }

    /**
     * Generate Code
     *
     * @param mixed $type
     * @param mixed $id
     * @return string
     * @SuppressWarnings(PHPMD)
     */
    public function generateCode($type = 'request', $id = null)
    {
        if ($type === 'delivery') {
            $i = 0;
            do {
                if (!$id) {
                    $id = $this->deliveryCollection->getLastItem()->getId() + 1;
                }
                $code = trim(sprintf("DN%'.09d\n", $id + $i));
                $i++;
            } while (!$this->validateDeliveryCode($code) && $i < 1000);
        } elseif ($type === 'receive') {
            $i = 0;
            do {
                if (!$id) {
                    $id = $this->receiveCollection->getLastItem()->getId() + 1;
                }
                $code = trim(sprintf("RN%'.09d\n", $id + $i));
                $i++;
            } while (!$this->validateReceiveCode($code) && $i < 1000);
        } else {
            $i = 0;
            do {
                if (!$id) {
                    $id = $this->branchRequestCollection->getLastItem()->getId() + 1;
                }
                $code = trim(sprintf("BR%'.09d\n", $id + $i));
                $i++;
            } while (!$this->validateBranchRequestId($code) && $i < 1000);
        }
        return $code;
    }

    /**
     * Validate Branch Request Id
     *
     * @param mixed $branchRequestId
     * @param mixed $id
     * @return bool
     */
    public function validateBranchRequestId($branchRequestId, $id = null)
    {
        $branchRequestCollection = $this->branchRequestCollection->addFieldToFilter(
            InventoryTransfer::CODE,
            $branchRequestId
        );
        if ($id) {
            $branchRequestCollection->addFieldToFilter(InventoryTransfer::ID, ['neq' => [$id]]);
        }
        if ($branchRequestCollection->getSize()) {
            return false;
        }
        return true;
    }

    /**
     * Validate Delivery Code
     *
     * @param mixed $code
     * @param mixed $id
     * @return bool
     */
    public function validateDeliveryCode($code, $id = null)
    {
        $deliveryCollection = $this->deliveryCollection->addFieldToFilter(
            Delivery::CODE,
            $code
        );
        if ($id) {
            $deliveryCollection->addFieldToFilter(Delivery::DELIVERY_ID, ['neq' => [$id]]);
        }
        if ($deliveryCollection->getSize()) {
            return false;
        }
        return true;
    }

    /**
     * Validate Receive Code
     *
     * @param mixed $code
     * @param mixed $id
     * @return bool
     */
    public function validateReceiveCode($code, $id = null)
    {
        $receiveCollection = $this->receiveCollection->addFieldToFilter(
            Receive::CODE,
            $code
        );
        if ($id) {
            $receiveCollection->addFieldToFilter(Receive::RECEIVE_ID, ['neq' => [$id]]);
        }
        if ($receiveCollection->getSize()) {
            return false;
        }
        return true;
    }
}
