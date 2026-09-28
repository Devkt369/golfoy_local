<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\InventoryTransfer;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\ReceiveProductInterface;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\Collection as TransferItems;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\CollectionFactory
    as TransferItemsFactory;

/**
 * Class BranchRequest DeliveryProduct
 */
class DeliveryProduct extends \Magento\Framework\Model\AbstractModel implements ReceiveProductInterface
{
    /**
     * @var TransferItemsFactory
     */
    public $transferItemsFactory;

    /**
     * @var Delivery
     */
    public $delivery;

    /**
     * ReceiveProduct construct
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\DeliveryProduct::class);
    }

    /**
     * DeliveryProduct construct
     *
     * @param TransferItemsFactory $transferItemsFactory
     * @param Delivery $delivery
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        TransferItemsFactory    $transferItemsFactory,
        Delivery         $delivery,
        Context          $context,
        Registry         $registry,
        AbstractResource $resource = null,
        AbstractDb       $resourceCollection = null,
        array            $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->transferItemsFactory = $transferItemsFactory;
        $this->delivery = $delivery;
    }

    /**
     * Get Transfer Item
     *
     * @return InventoryTransferProduct
     */
    public function getTransferItem()
    {
        $delivery = $this->delivery->load($this->getDeliveryId());
        /** @var TransferItems $transferItems */
        $transferItems = $this->transferItemsFactory->create();
        return $transferItems->addFieldToFilter('inventorytransfer_id', $delivery->getInventorytransferId())
            ->addFieldToFilter('product_id', $this->getProductId())
            ->getFirstItem();
    }
}
