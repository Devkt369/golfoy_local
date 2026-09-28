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
 * Class BranchRequest ReceiveProduct
 */
class ReceiveProduct extends \Magento\Framework\Model\AbstractModel implements ReceiveProductInterface
{
    /**
     * @var TransferItemsFactory
     */
    public $transferItemsFactory;

    /**
     * @var Receive
     */
    public $receive;

    /**
     * ReceiveProduct construct
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct::class);
    }

    /**
     * ReceiveProduct construct
     *
     * @param TransferItemsFactory $transferItemsFactory
     * @param Receive $receive
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        TransferItemsFactory    $transferItemsFactory,
        Receive          $receive,
        Context          $context,
        Registry         $registry,
        AbstractResource $resource = null,
        AbstractDb       $resourceCollection = null,
        array            $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->transferItemsFactory = $transferItemsFactory;
        $this->receive = $receive;
    }

    /**
     * Get Transfer Item
     *
     * @return InventoryTransferProduct
     */
    public function getTransferItem()
    {
        $receive = $this->receive->load($this->getReceiveId());
        /** @var TransferItems $transferItems */
        $transferItems = $this->transferItemsFactory->create();
        return $transferItems->addFieldToFilter('inventorytransfer_id', $receive->getInventorytransferId())
            ->addFieldToFilter('product_id', $this->getProductId())
            ->getFirstItem();
    }
}
