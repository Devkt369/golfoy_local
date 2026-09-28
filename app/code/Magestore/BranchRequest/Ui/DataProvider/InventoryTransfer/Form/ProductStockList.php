<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\DataProvider\InventoryTransfer\Form;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Class BranchRequest ProductStockList
 */
class ProductStockList extends ProductDataProvider
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $collection;

    /**
     * @var array
     */
    protected $addFieldStrategies;

    /**
     * @var array
     */
    protected $addFilterStrategies;

    /**
     * @var \Magestore\BranchRequest\Model\InventoryTransferFactory
     */
    protected $inventoryTransferFactory;

    /**
     * @var array
     */
    protected $addedField;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $type;

    /**
     * ProductStockList construct
     *
     * @param mixed $name
     * @param mixed $primaryFieldName
     * @param mixed $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param \Magestore\BranchRequest\Model\InventoryTransferFactory $inventoryTransferFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Catalog\Model\Product\Type $type
     * @param array $addFieldStrategies
     * @param array $addFilterStrategies
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Magestore\BranchRequest\Model\InventoryTransferFactory $inventoryTransferFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Catalog\Model\Product\Type $type,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory,
            $addFieldStrategies,
            $addFilterStrategies,
            $meta,
            $data
        );
        $this->request = $request;
        $this->inventoryTransferFactory = $inventoryTransferFactory;
        $this->collection = $this->getProductCollection();
        $this->addFieldStrategies = $addFieldStrategies;
        $this->addFilterStrategies = $addFilterStrategies;
        $this->type = $type;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        $items = $this->getCollection()->toArray();
        $productTypes = $this->type->getOptionArray();
        foreach ($items as &$item) {
            if (isset($item['type_id'])) {
                $item['type'] = $productTypes[$item['type_id']];
            }
        }

        $data = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => array_values($items),
        ];

        return $data;
    }

    /**
     * Get Product Collection
     *
     * @return mixed
     */
    public function getProductCollection()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->create(
            \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\GlobalStock\Collection::class
        );

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
        $this->addedField[] = $filter->getField();
        if ($filter->getField() == "source_warehouse_code" && $filter->getValue()) {
            $this->getCollection()->addSourceCodeToFilter($filter->getValue());
        } elseif ($filter->getField() == "barcode" && $filter->getValue()) {
            $this->getCollection()->addBarcodeToFilter($filter->getValue());
        } else {
            parent::addFilter($filter);
        }
        return $this;
    }
}
