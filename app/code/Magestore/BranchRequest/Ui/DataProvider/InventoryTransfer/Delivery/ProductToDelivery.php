<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\DataProvider\InventoryTransfer\Delivery;

use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\CollectionFactory
    as InventoryTransferProductCollectionFactory;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\Collection
    as InventoryTransferProductCollection;

/**
 * Class BranchRequest ProductToDelivery
 */
class ProductToDelivery extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\Collection
     */
    public $collection;

    /**
     * @var InventoryTransferProductCollectionFactory
     */
    public $collectionFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    public $request;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    public $type;

    /**
     * ProductToReceive construct
     *
     * @param mixed $name
     * @param mixed $primaryFieldName
     * @param mixed $requestFieldName
     * @param InventoryTransferProductCollectionFactory $collectionFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\Product\Type $type
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        InventoryTransferProductCollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Product\Type $type,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;

        $this->collectionFactory = $collectionFactory;
        $this->objectManager = $objectManager;
        $this->type = $type;
        $this->collection = $this->getProductCollection();
    }

    /**
     * Get Product Collection
     *
     * @return InventoryTransferProductCollection
     */
    public function getProductCollection()
    {
        $inventoryTransferId = $this->request->getParam('inventorytransfer_id');
        /** @var InventoryTransferProductCollection $collection */
        $collection = $this->collectionFactory->create()->getTransferedProducts($inventoryTransferId);
        $cond = '(qty_requested - qty_delivered > 0)';
        $collection->getSelect()->where($cond);
        return $collection;
    }

    /**
     * Add Filter
     *
     * @param \Magento\Framework\Api\Filter $filter
     * @return mixed|void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'product_sku' || $filter->getField() == 'product_id') {
            $filter->setField('main_table.' . $filter->getField());
        }
        return parent::addFilter($filter);
    }

    /**
     * Get Data
     *
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        if ($this->request->getParam('inventorytransfer_id')) {
            /** @var \Magento\Catalog\Helper\Image $imageCalalogHelper */
            $imageCalalogHelper = $this->objectManager->get(\Magento\Catalog\Helper\Image::class);
            $productTypes = $this->type->getOptionArray();
            foreach ($data['items'] as &$item) {
                $item['available_qty_to_receive'] = (float)$item['available_qty_to_receive'];
                if (strpos($item['image_url'], 'no_selection') !== false) {
                    $item['image_url'] = $imageCalalogHelper->getDefaultPlaceholderUrl('thumbnail');
                }
                if (isset($item['type_id'])) {
                    $item['type'] = $productTypes[$item['type_id']];
                }
            }
        }
        return $data;
    }
}
