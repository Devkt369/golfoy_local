<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\DataProvider\InventoryTransfer\Delivery;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magestore\BranchRequest\Model\InventoryTransfer;
use Magestore\BranchRequest\Model\InventoryTransfer\Delivery;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\DeliveryProduct\Collection;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\DeliveryProduct\CollectionFactory;

/**
 * Class BranchRequest DeliveryProduct
 */
class DeliveryProduct extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    public $request;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magestore\BranchRequest\Model\InventoryTransferFactory
     */
    public $inventoryTransferFactory;

    /**
     * @var \Magestore\BranchRequest\Model\InventoryTransfer\DeliveryFactory
     */
    public $deliveryFactory;

    /**
     * ReceiveProduct constructor
     *
     * @param mixed $name
     * @param mixed $primaryFieldName
     * @param mixed $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magestore\BranchRequest\Model\InventoryTransferFactory $inventoryTransferFactory
     * @param InventoryTransfer\DeliveryFactory $deliveryFactory
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magestore\BranchRequest\Model\InventoryTransferFactory $inventoryTransferFactory,
        \Magestore\BranchRequest\Model\InventoryTransfer\DeliveryFactory $deliveryFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;

        $this->_collectionFactory = $collectionFactory;
        $this->_objectManager = $objectManager;
        $this->inventoryTransferFactory = $inventoryTransferFactory;
        $this->deliveryFactory = $deliveryFactory;
        $this->collection = $this->getProductCollection();
    }

    /**
     * Get Product Collection
     *
     * @return Collection
     */
    public function getProductCollection()
    {
        $deliveryId = $this->request->getParam('delivery_id');
        /** @var Collection $collection */
        $collection = $this->_collectionFactory->create();
        $collection->getImageProduct();
        $collection->getProductType();
        $collection->addFieldToFilter("delivery_id", $deliveryId);
        /** @var Delivery $delivery */
        $delivery = $this->deliveryFactory->create()->load($deliveryId);
        /** @var InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->inventoryTransferFactory->create()->load($delivery->getInventorytransferId());
        if ($sendingSourceCode = $inventoryTransfer->getData(InventoryTransferInterface::FROM)) {
            $sourceItemTable = $collection->getTable('inventory_source_item');
            $collection->getSelect()->joinLeft(
                ['current_inventory_source_item' => $sourceItemTable],
                "main_table.product_sku = current_inventory_source_item.sku AND
                current_inventory_source_item.source_code = '$sendingSourceCode'",
                ['racket']
            );
        }
        return $collection;
    }

    /**
     * Get Data
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        if ($this->request->getParam('delivery_id')) {
            /** @var \Magento\Catalog\Helper\Image $imageCalalogHelper */
            $imageCalalogHelper = $this->_objectManager->get(\Magento\Catalog\Helper\Image::class);
            foreach ($data['items'] as &$item) {
                $item['qty'] = (float)$item['qty'];
                if (strpos($item['image_url'], 'no_selection') !== false) {
                    $item['image_url'] = $imageCalalogHelper->getDefaultPlaceholderUrl('thumbnail');
                }
            }
        }
        return $data;
    }
}
