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
use Magestore\BranchRequest\Api\Data\InventoryTransfer\DeliveryInterface;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\DeliveryProduct\Collection as DeliveryItems;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\DeliveryProduct\CollectionFactory
    as DeliveryItemsFactory;

/**
 * Class BranchRequest Delivery
 */
class Delivery extends \Magento\Framework\Model\AbstractModel implements DeliveryInterface
{
    /**
     * @var DeliveryItemsFactory
     */
    public $deliveryItemsFactory;

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Delivery::class);
    }

    /**
     * Delivery construct
     *
     * @param DeliveryItemsFactory $deliveryItemsFactory
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        DeliveryItemsFactory    $deliveryItemsFactory,
        Context          $context,
        Registry         $registry,
        AbstractResource $resource = null,
        AbstractDb       $resourceCollection = null,
        array            $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->deliveryItemsFactory = $deliveryItemsFactory;
    }

    /**
     * Get Delivery Items
     *
     * @return DeliveryItems
     */
    public function getDeliveryItems()
    {
        /** @var DeliveryItems $deliveryItems */
        $deliveryItems = $this->deliveryItemsFactory->create();
        return $deliveryItems->addFieldToFilter('delivery_id', $this->getId());
    }
}
