<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\InventoryTransfer;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\ReceiveInterface;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\Collection as ReceiveItems;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\CollectionFactory
    as ReceiveItemsFactory;

/**
 * Class BranchRequest Reveive
 */
class Receive extends \Magento\Framework\Model\AbstractModel implements ReceiveInterface
{
    /**
     * @var ReceiveItemsFactory
     */
    public $receiveItemsFactory;

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Receive::class);
    }

    /**
     * Receive construct
     *
     * @param ReceiveItemsFactory $receiveItemsFactory
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ReceiveItemsFactory    $receiveItemsFactory,
        Context          $context,
        Registry         $registry,
        AbstractResource $resource = null,
        AbstractDb       $resourceCollection = null,
        array            $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->receiveItemsFactory = $receiveItemsFactory;
    }

    /**
     * Get Receive Items
     *
     * @return ReceiveItems
     */
    public function getReceiveItems()
    {
        /** @var ReceiveItems $receiveItems */
        $receiveItems = $this->receiveItemsFactory->create();
        return $receiveItems->addFieldToFilter('receive_id', $this->getId());
    }
}
