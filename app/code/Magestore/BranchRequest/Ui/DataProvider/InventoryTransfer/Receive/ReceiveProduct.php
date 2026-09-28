<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\DataProvider\InventoryTransfer\Receive;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magestore\BranchRequest\Model\InventoryTransfer;
use Magestore\BranchRequest\Model\InventoryTransfer\Receive;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\CollectionFactory;

/**
 * Class BranchRequest ReceiveProduct
 */
class ReceiveProduct extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\Collection
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
     * @var \Magestore\BranchRequest\Model\InventoryTransfer\ReceiveFactory
     */
    public $receiveFactory;

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
     * @param InventoryTransfer\ReceiveFactory $receiveFactory
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
        \Magestore\BranchRequest\Model\InventoryTransfer\ReceiveFactory $receiveFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;

        $this->_collectionFactory = $collectionFactory;
        $this->_objectManager = $objectManager;
        $this->inventoryTransferFactory = $inventoryTransferFactory;
        $this->receiveFactory = $receiveFactory;
        $this->collection = $this->getProductCollection();
    }

    /**
     * Get Product Collection
     *
     * @return \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\Collection
     */
    public function getProductCollection()
    {
        $receiveId = $this->request->getParam('receive_id');
        /** @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\Collection $collection */
        $collection = $this->_collectionFactory->create();
        $collection->getImageProduct();
        $collection->getProductType();
        $collection->addFieldToFilter("receive_id", $receiveId);
        /** @var Receive $receive */
        $receive = $this->receiveFactory->create()->load($receiveId);
        /** @var InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->inventoryTransferFactory->create()->load($receive->getInventorytransferId());
        if ($sendingSourceCode = $inventoryTransfer->getData(InventoryTransferInterface::TO)) {
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
        if ($this->request->getParam('receive_id')) {
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
