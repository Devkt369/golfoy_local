<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\TransferStock\Ui\DataProvider\InventoryTransfer\Edit\Form;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magestore\TransferStock\Api\TransferManagementInterface;
use Magestore\TransferStock\Model\InventoryTransfer\Option\Status;
use Magestore\TransferStock\Model\ResourceModel\InventoryTransfer\CollectionFactory;
use Magestore\TransferStock\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\Collection;
use Magestore\TransferStock\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\CollectionFactory
    as InventoryTransferProductCollectionFactory;

/**
 * Class InventoryTransfer
 *
 * Used for edit form inventory transfer
 */
class InventoryTransfer extends AbstractDataProvider
{
    /**
     * @var PoolInterface
     */
    protected $pool;
    /**
     * @var Registry
     */
    protected $_coreRegistry;
    /**
     * @var TransferManagementInterface
     */
    protected $transferManagement;
    /**
     * @var \Magestore\TransferStock\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\CollectionFactory
     */
    protected $transferProductCollectionFactory;
    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var array
     */
    protected $loadData = [];
    /**
     * @var Type
     */
    protected $type;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param PoolInterface $pool
     * @param Registry $registry
     * @param TransferManagementInterface $transferManagement
     * @param InventoryTransferProductCollectionFactory $transferProductCollectionFactory
     * @param SourceRepositoryInterface $sourceRepository
     * @param ObjectManagerInterface $objectManager
     * @param Type $type
     * @param array $meta
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        PoolInterface $pool,
        Registry $registry,
        TransferManagementInterface $transferManagement,
        InventoryTransferProductCollectionFactory $transferProductCollectionFactory,
        SourceRepositoryInterface $sourceRepository,
        ObjectManagerInterface $objectManager,
        Type $type,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->pool = $pool;
        $this->_coreRegistry = $registry;
        $this->transferManagement = $transferManagement;
        $this->transferProductCollectionFactory = $transferProductCollectionFactory;
        $this->sourceRepository = $sourceRepository;
        $this->_objectManager = $objectManager;
        $this->type = $type;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getData()
    {
        if ($this->loadData) {
            return $this->loadData;
        }

        /** @var \Magestore\TransferStock\Model\InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->_coreRegistry->registry('current_inventory_transfer');
        if ($inventoryTransfer && $inventoryTransfer->getId()) {
            /** @var Image $imageCalalogHelper */
            $imageCalalogHelper = $this->_objectManager->get(Image::class);
            $inventoryTransferData = $inventoryTransfer->getData();

            // get source name
            $inventoryTransferData['source_warehouse_name']
                = $this->sourceRepository->get($inventoryTransfer->getSourceWarehouseCode())->getName();
            $inventoryTransferData['des_warehouse_name']
                = $this->sourceRepository->get($inventoryTransfer->getDesWarehouseCode())->getName();
            $inventoryTransferData['status_label']
                = ($inventoryTransferData['status'] == Status::STATUS_OPEN) ? __('Open') : __('Closed');

            $this->loadData[$inventoryTransfer->getId()]['transfer_summary']['general_information']
                = $inventoryTransferData;
            $products = $this->getProductsListData(
                $inventoryTransfer->getId(),
                $inventoryTransfer->getSourceWarehouseCode()
            );
            $productTypes = $this->type->getOptionArray();
            if (count($products)) {
                foreach ($products as $product) {
                    if (strpos($product['image_url'], 'no_selection') !== false) {
                        $product['image_url'] = $imageCalalogHelper->getDefaultPlaceholderUrl('thumbnail');
                    }
                    $dataProduct = [
                        'id' => $product['product_id'],
                        'sku' => $product['product_sku'],
                        'name' => $product['product_name'],
                        'qty' => (float)$product['quantity'],
                        'qty_transferred' => (float)$product['qty_transferred'],
                        'qty_received' => (float)$product['qty_received'],
                        'image' => $product['image_url'],
                        'barcode' => $product['barcode'] ?? '',
                        'type' => isset($productTypes[$product['type_id']])
                            ? $productTypes[$product['type_id']]
                            : $product['type_id']
                    ];
                    $this->loadData[$inventoryTransfer->getId()]['transfer_summary']['product_list']
                        ['product_dynamic_grid']['links']['product_list'][] = $dataProduct;
                }
            }
        }

        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $this->loadData = $modifier->modifyData($this->loadData);
        }
        return $this->loadData;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * Get product list data for transfer
     *
     * @param int $transferId
     * @param string $sendingSourceCode
     * @return array
     */
    public function getProductsListData($transferId, $sendingSourceCode)
    {
        /** @var Collection $collection */
        $collection = $this->transferProductCollectionFactory->create();
        $collection->getTransferedProducts($transferId, $sendingSourceCode);
        return $collection->getData();
    }
}
