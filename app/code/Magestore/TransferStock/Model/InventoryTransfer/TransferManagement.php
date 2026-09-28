<?php

namespace Magestore\TransferStock\Model\InventoryTransfer;

use Magento\Backend\Model\Auth\Session;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryImportExport\Model\Import\SourceItemConvert;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryImportExport\Model\Import\Sources;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\TransferStock\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magestore\TransferStock\Api\Data\InventoryTransfer\InventoryTransferInterfaceFactory;
use Magestore\TransferStock\Api\Data\InventoryTransfer\InventoryTransferProductInterface;
use Magestore\TransferStock\Api\Data\InventoryTransfer\InventoryTransferProductInterfaceFactory;
use Magestore\TransferStock\Api\Data\InventoryTransfer\ReceiveInterface;
use Magestore\TransferStock\Api\Data\InventoryTransfer\ReceiveInterfaceFactory;
use Magestore\TransferStock\Api\Data\InventoryTransfer\ReceiveProductInterface;
use Magestore\TransferStock\Api\Data\InventoryTransfer\ReceiveProductInterfaceFactory;
use Magestore\TransferStock\Api\MultiSourceInventory\SourceManagementInterface;
use Magestore\TransferStock\Api\TransferManagementInterface;
use Magestore\TransferStock\Model\InventoryTransfer\Option\Stage;
use Magestore\TransferStock\Model\InventoryTransfer\Option\Status;
use Magestore\TransferStock\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\Collection;
use Magestore\TransferStock\Model\ResourceModel\InventoryTransfer\ReceiveProduct\CollectionFactory;
use Magestore\TransferStock\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\CollectionFactory
    as ProductCollectionFactory;
use Magestore\TransferStock\Model\ResourceModel\InventoryTransfer\GlobalStock\Collection as GlobalStockCollection;
use Magento\Framework\Serialize\Serializer\Json as JsonSerialize;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Service Transfer Management
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class TransferManagement implements TransferManagementInterface
{
    const MOVEMENT_MAGESTORE_ACTION_TYPE = 'magestore_transfer';
    /**
     * @var InventoryTransferInterfaceFactory
     */
    protected $inventoryTransferFactory;
    /**
     * @var SourceItemConvert
     */
    protected $sourceItemConvert;
    /**
     * @var SourceItemsSaveInterface
     */
    protected $sourceItemsSave;
    /**
     * @var ProductCollectionFactory
     */
    protected $inventoryTransferProductCollectionFactory;
    /**
     * @var SourceManagementInterface
     */
    protected $sourceManagement;
    /**
     * @var InventoryTransferProductInterfaceFactory
     */
    protected $inventoryTransferProductFactory;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var ReceiveInterfaceFactory
     */
    protected $receiveInterfaceFactory;
    /**
     * @var ReceiveProductInterfaceFactory
     */
    protected $receiveProductInterfaceFactory;
    /**
     * @var Session
     */
    protected $authSession;
    /**
     * @var Type
     */
    protected $type;
    /**
     * @var CollectionFactory
     */
    protected $inventoryReceiveProductCollectionFactory;
    /**
     * @var EventManager
     */
    protected $eventManager;
    /**
     * @var GetCurrentUserInterface
     */
    protected $getCurrentUser;
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * TransferManagement constructor.
     *
     * @param InventoryTransferInterfaceFactory $inventoryTransferFactory
     * @param SourceItemConvert $sourceItemConvert
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param ProductCollectionFactory $inventoryTransferProductCollectionFactory
     * @param CollectionFactory $inventoryReceiveProductCollectionFactory
     * @param SourceManagementInterface $sourceManagement
     * @param InventoryTransferProductInterfaceFactory $inventoryTransferProductFactory
     * @param ObjectManagerInterface $objectManager
     * @param ReceiveInterfaceFactory $receiveInterfaceFactory
     * @param ReceiveProductInterfaceFactory $receiveProductInterfaceFactory
     * @param Session $authSession
     * @param Type $type
     * @param EventManager $eventManager
     * @param GetCurrentUserInterface $getCurrentUser
     * @param DateTime $dateTime
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        InventoryTransferInterfaceFactory $inventoryTransferFactory,
        SourceItemConvert $sourceItemConvert,
        SourceItemsSaveInterface $sourceItemsSave,
        ProductCollectionFactory $inventoryTransferProductCollectionFactory,
        CollectionFactory $inventoryReceiveProductCollectionFactory,
        SourceManagementInterface $sourceManagement,
        InventoryTransferProductInterfaceFactory $inventoryTransferProductFactory,
        ObjectManagerInterface $objectManager,
        ReceiveInterfaceFactory $receiveInterfaceFactory,
        ReceiveProductInterfaceFactory $receiveProductInterfaceFactory,
        Session $authSession,
        Type $type,
        EventManager $eventManager,
        GetCurrentUserInterface $getCurrentUser,
        DateTime $dateTime
    ) {
        $this->inventoryTransferFactory = $inventoryTransferFactory;
        $this->sourceItemConvert = $sourceItemConvert;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->inventoryTransferProductCollectionFactory = $inventoryTransferProductCollectionFactory;
        $this->sourceManagement = $sourceManagement;
        $this->inventoryTransferProductFactory = $inventoryTransferProductFactory;
        $this->_objectManager = $objectManager;
        $this->receiveInterfaceFactory = $receiveInterfaceFactory;
        $this->receiveProductInterfaceFactory = $receiveProductInterfaceFactory;
        $this->authSession = $authSession;
        $this->type = $type;
        $this->inventoryReceiveProductCollectionFactory = $inventoryReceiveProductCollectionFactory;
        $this->eventManager = $eventManager;
        $this->getCurrentUser = $getCurrentUser;
        $this->dateTime = $dateTime;
    }

    /**
     * @inheritDoc
     */
    public function startToSendStock($transferId)
    {
        $result = [
            'status' => false
        ];
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($transferId);
        if (!$transferInventory->getInventorytransferId()) {
            $result['message'] = __('Could not find transfer inventory request with ID %1', $transferId);
            return $result;
        } elseif ($transferInventory->getStatus() == Status::STATUS_CLOSED) {
            $result['message'] = __('The transfer inventory request has already closed.');
            return $result;
        }

        $items = $this->getProductFromInventoryTransfer($transferId);
        if (!count($items)) {
            $result['message'] = __('Please select item(s) before sending.');
            return $result;
        }

        $this->processSendProduct($transferInventory, $items);

        $transferInventory->setStage(Stage::STAGE_SENT)->save();

        $result = [
            'status' => true,
            'message' => __('The stock has been sent successfully.')
        ];
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getProductFromInventoryTransfer($transferId)
    {
        /** @var Collection $collection */
        $collection = $this->inventoryTransferProductCollectionFactory->create();
        $collection->addTransferIdToFilter($transferId);
        return $collection;
    }

    /**
     * Process Send Product
     *
     * @param InventoryTransferInterface $transferInventory
     * @param InventoryTransferProductInterface[] $items
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    public function processSendProduct($transferInventory, $items)
    {
        $sentItems = [];
        $movementsData = [];

        if ($this->isEnableInventoryMovement()) {
            $currentUser = $this->getCurrentUser->getCurrentUser();
            $metaData = [
                InventoryMovementInterface::ACTION_LABEL => __('New Transfer Stock No'),
                InventoryMovementInterface::OBJECT_TYPE => self::MOVEMENT_MAGESTORE_ACTION_TYPE,
                InventoryMovementInterface::OBJECT_ID => $transferInventory->getInventorytransferId(),
                InventoryMovementInterface::OBJECT_INCREMENT_ID => $transferInventory->getInventorytransferCode()
            ];
        }

        foreach ($items as $item) {
            $sourceItems = $this->sourceManagement->getSourceItemsMap(
                $item->getProductSku(),
                [$transferInventory->getSourceWarehouseCode()]
            );
            $oldQty = $sourceItems[$transferInventory->getSourceWarehouseCode()]->getQuantity();
            $sentItems[$item->getProductSku()] = $oldQty - $item->getQtyTransferred();

            if ((int)$item->getQtyTransferred() == 0) {
                continue;
            }

            if ($this->isEnableInventoryMovement()) {
                $movement = [
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::ACTION_TYPE => self::MOVEMENT_MAGESTORE_ACTION_TYPE,
                    InventoryMovementInterface::SOURCE_CODE => $transferInventory->getSourceWarehouseCode(),
                    InventoryMovementInterface::IS_IN_STOCK => ($sentItems[$item->getProductSku()] > 0) ? 1 : 0,
                    InventoryMovementInterface::PRODUCT_SKU => $item->getProductSku(),
                    InventoryMovementInterface::OLD_QTY => $oldQty,
                    InventoryMovementInterface::CHANGE_QTY => -$item->getQtyTransferred(),
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate()
                ];
                if (count($metaData)) {
                    $movement[InventoryMovementInterface::METADATA] = $metaData;
                }
                if ($currentUser && $currentUser->getUserId()) {
                    $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                    $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                    $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                }
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();

                $movementsData[] = $movement;
            }
        }

        $this->processChangeQuantity($transferInventory->getSourceWarehouseCode(), $sentItems, $movementsData);
    }

    /**
     * Process Change Quantity
     *
     * @param string $sourceCode
     * @param array $items
     * @param array $movementsData
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    public function processChangeQuantity(string $sourceCode, array $items, array $movementsData = [])
    {
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);

        $sourceItems = [];
        $packItems = 0;
        $i = 0;

        foreach ($items as $sku => $qty) {
            $sourceItem = [
                Sources::COL_SOURCE_CODE => $sourceCode,
                Sources::COL_SKU => (string)$sku,
                Sources::COL_QTY => $qty,
                Sources::COL_STATUS => ($qty > 0) ? 1 : 0
            ];
            $sourceItems[$packItems][] = $sourceItem;
            $i++;
            if ($i == 500) {
                $i = 0;
                $packItems++;
            }
        }

        if (count($movementsData)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }
        if (!empty($sourceItems)) {
            foreach ($sourceItems as $itemsData) {
                $itemsData = $this->sourceItemConvert->convert($itemsData);
                $this->sourceItemsSave->execute($itemsData);
            }
        }

        if ($this->isEnableInventoryMovement()) {
            /* Save log after processing */
            foreach (array_keys($movementsData) as $key) {
                $movementsData[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
                $sku = $movementsData[$key][InventoryMovementInterface::PRODUCT_SKU];
                $movementsData[$key][InventoryMovementInterface::NEW_QTY] = $items[$sku];
            }
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteProductsInInventoryTransfer($transferId)
    {
        $products = $this->getProductFromInventoryTransfer($transferId);
        foreach ($products as $product) {
            $product->delete();
        }

        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($transferId);
        $transferInventory->setQtyTransferred(0)->save();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function setProductsForInventoryTransfer($transferId, $items)
    {
        $this->deleteProductsInInventoryTransfer($transferId);

        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($transferId);
        $totalQtyToTransfer = 0;

        foreach ($items as $item) {
            /** @var InventoryTransferProductInterface $transferProduct */
            $transferProduct = $this->inventoryTransferProductFactory->create();
            $data = [
                'inventorytransfer_id' => $transferId,
                'product_id' => $item['id'],
                'product_name' => $item['name'],
                'product_sku' => $item['sku'],
                'qty_transferred' => isset($item['qty_transferred']) ? $item['qty_transferred'] : 0,
                'qty_received' => isset($item['qty_received']) ? $item['qty_received'] : 0
            ];
            $transferProduct->setData($data)->save();

            $totalQtyToTransfer += (float)$item['qty_transferred'];
        }

        $transferInventory->setQtyTransferred($totalQtyToTransfer)->save();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getSelectBarcodeProductListJson($productIds = [])
    {
        $collection = $this->getProductCollection();
        if (count($productIds)) {
            $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
        }
        if (!$collection->isLoaded()) {
            $collection->load();
        }
        $items = $collection->toArray();
        return $this->convertProductSendResultToJson($items);
    }

    /**
     * Get product json by barcode function
     *
     * @param string $barcode
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductJsonByBarcode(string $barcode)
    {
        /* Filter and limit 1 record */
        $collection = $this->getProductCollection();
        $collection->getSelect()->where(
            $collection->getConnection()->prepareSqlCondition('barcode.barcode', ['finset' => $barcode])
        );
        $collection->setPageSize(1)->setCurPage(1);
        if (!$collection->isLoaded()) {
            $collection->load();
        }
        $items = $collection->toArray();
        return $this->convertProductSendResultToJson($items);
    }

    /**
     * Convert Result To Json
     *
     * @param array $items
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function convertProductSendResultToJson(array $items)
    {
        $result = [];
        foreach ($items as $item) {
            if (isset($item['barcode'])) {
                $barcodes = explode(',', (string)$item['barcode']);
                foreach ($barcodes as $barcode) {
                    $result[$barcode] = $item;
                }
            }
        }
        // set image url
        $storeManager = $this->_objectManager->get(StoreManagerInterface::class);
        $path = $storeManager->getStore()->getBaseUrl(
            UrlInterface::URL_TYPE_MEDIA
        );

        $productTypes = $this->type->getOptionArray();

        /* @var Image $imageCalalogHelper */
        $imageCalalogHelper = $this->_objectManager->get(Image::class);
        foreach ($result as &$item) {
            if (isset($item['thumbnail'])) {
                if ($item['thumbnail'] != 'no_selection') {
                    $item['thumbnail_src'] = $path . 'catalog/product' . $item['thumbnail'];
                } else {
                    $item['thumbnail_src'] = $imageCalalogHelper->getDefaultPlaceholderUrl('thumbnail');
                }
            }
            if (isset($item['type_id'])) {
                $item['type'] = isset($productTypes[$item['type_id']])
                    ? $productTypes[$item['type_id']]
                    : $item['type_id'];
            }
            if (isset($item['quantity'])) {
                $item['quantity'] = (float)$item['quantity'];
            }
        }

        return $this->_objectManager
            ->get(JsonSerialize::class)
            ->serialize($result);
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getSelectBarcodeReceivingProductListJson($transferId)
    {
        /** @var Collection $collection */
        $collection = $this->inventoryTransferProductCollectionFactory->create();
        $collection->getTransferedProducts($transferId);
        $collection->getSelect()->where('((qty_transferred - qty_received > 0) OR (qty_transferred = 0))');
        if (!$collection->isLoaded()) {
            $collection->load();
        }

        $items = $collection->getData();
        return $this->convertReceivingBarcodeResultToJson($items);
    }

    /**
     * Get product json by transfer id and barcode function
     *
     * @param int $transferId
     * @param string $barcode
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductReceivingJsonByBarcode(int $transferId, string $barcode)
    {
        /** @var Collection $collection */
        $collection = $this->inventoryTransferProductCollectionFactory->create();
        $collection->getTransferedProducts($transferId);
        $collection->getSelect()->where('((qty_transferred - qty_received > 0) OR (qty_transferred = 0))');
        $collection->getSelect()->where(
            $collection->getConnection()->prepareSqlCondition('barcode.barcode', ['finset' => $barcode])
        );
        $collection->setPageSize(1)->setCurPage(1);
        if (!$collection->isLoaded()) {
            $collection->load();
        }
        $items = $collection->getData();
        return $this->convertReceivingBarcodeResultToJson($items);
    }

    /**
     * Convert Result To Json
     *
     * @param array $items
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function convertReceivingBarcodeResultToJson(array $items)
    {
        $result = [];
        /** @var Image $imageCalalogHelper */
        $imageCalalogHelper = $this->_objectManager->get(Image::class);

        $productTypes = $this->type->getOptionArray();

        foreach ($items as $item) {
            if (isset($item['image_url']) && strpos($item['image_url'], 'no_selection') !== false) {
                $item['image_url'] = $imageCalalogHelper->getDefaultPlaceholderUrl('thumbnail');
            }
            if (isset($item['barcode'])) {
                $barcodes = explode(',', (string)$item['barcode']);
                foreach ($barcodes as $barcode) {
                    $result[$barcode] = $item;
                }
            }
        }

        foreach ($result as &$item) {
            if (isset($item['type_id'])) {
                $item['type'] = isset($productTypes[$item['type_id']])
                    ? $productTypes[$item['type_id']]
                    : $item['type_id'];
            }

            $item['available_qty_to_receive'] = (float)$item['available_qty_to_receive'];
        }

        return $this->_objectManager
            ->get(JsonSerialize::class)
            ->serialize($result);
    }

    /**
     * Get Product Collection
     *
     * @return GlobalStockCollection
     */
    public function getProductCollection()
    {
        $collection = $this->_objectManager->create(
            GlobalStockCollection::class
        );

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function addProductsToInventoryTransfer($transferId, $items)
    {
        $products = $this->getProductFromInventoryTransfer($transferId);
        // get list current products
        $currentProducts = [];
        foreach ($products as $product) {
            $currentProducts[$product->getProductSku()] = $product;
        }

        // Add & update products
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($transferId);
        $totalQtyToTransfer = $transferInventory->getQtyTransferred();
        foreach ($items as $item) {
            // Update existing product
            if (isset($currentProducts[$item['sku']])) {
                /** @var InventoryTransferProductInterface $currentItem */
                $currentItem = $currentProducts[$item['sku']];
                $newQtyToTransfer = isset($item['qty_transferred']) ? $item['qty_transferred'] : 0;
                $totalQtyToTransfer = $totalQtyToTransfer
                    - (float)$currentItem->getQtyTransferred()
                    + (float)$newQtyToTransfer;
                $currentItem->setQtyTransferred($newQtyToTransfer)->save();
                continue;
            }

            // Add new product item
            /** @var InventoryTransferProductInterface $transferProduct */
            $transferProduct = $this->inventoryTransferProductFactory->create();
            $data = [
                'inventorytransfer_id' => $transferId,
                'product_id' => $item['id'],
                'product_name' => $item['name'],
                'product_sku' => $item['sku'],
                'qty_transferred' => isset($item['qty_transferred']) ? $item['qty_transferred'] : 0,
                'qty_received' => isset($item['qty_received']) ? $item['qty_received'] : 0
            ];
            $transferProduct->setData($data)->save();

            $totalQtyToTransfer += (float)$item['qty_transferred'];
        }

        $transferInventory->setQtyTransferred($totalQtyToTransfer)->save();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function receiveProducts($transferId, $items)
    {
        $receiveItemsData = [];
        $totalReceiveQty = 0;

        foreach ($items as $item) {
            $totalReceiveQty += $item['qty'];
            $receiveItemsData[$item['product_sku']] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'],
                'qty' => $item['qty']
            ];
        }

        /** @var ReceiveInterface $receive */
        $receive = $this->receiveInterfaceFactory->create();
        $receive->setCreatedBy($this->authSession->getUser()->getUserName());
        $receive->setInventorytransferId($transferId);
        $receive->setTotalQty($totalReceiveQty);
        $receive->save();

        foreach ($receiveItemsData as $itemDatum) {
            /** @var ReceiveProductInterface $receiveItem */
            $receiveItem = $this->receiveProductInterfaceFactory->create();
            $receiveItem->setData($itemDatum);
            $receiveItem->setReceiveId($receive->getReceiveId());
            $receiveItem->save();
        }

        $this->increaseProductQtyInDestinationSource($transferId, $receiveItemsData);

        $this->updateQuantityInTransferStock($transferId, $receiveItemsData, $totalReceiveQty);

        return true;
    }

    /**
     * Increase Product Qty In DestinationSource
     *
     * @param int $transferId
     * @param array $receiveItemsData
     * @return bool
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function increaseProductQtyInDestinationSource(int $transferId, array $receiveItemsData)
    {
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($transferId);

        $receiveItems = [];
        $movementsData = [];

        if ($this->isEnableInventoryMovement()) {
            $currentUser = $this->getCurrentUser->getCurrentUser();
            $metaData = [
                InventoryMovementInterface::ACTION_LABEL => __('New Transfer Stock No'),
                InventoryMovementInterface::OBJECT_TYPE => self::MOVEMENT_MAGESTORE_ACTION_TYPE,
                InventoryMovementInterface::OBJECT_ID => $transferInventory->getInventorytransferId(),
                InventoryMovementInterface::OBJECT_INCREMENT_ID => $transferInventory->getInventorytransferCode()
            ];
        }

        foreach ($receiveItemsData as $sku => $item) {
            $sourceItems = $this->sourceManagement->getSourceItemsMap(
                $sku,
                [$transferInventory->getDesWarehouseCode()]
            );
            $oldQty = isset($sourceItems[$transferInventory->getDesWarehouseCode()])
                ? $sourceItems[$transferInventory->getDesWarehouseCode()]->getQuantity()
                : 0;
            $receiveItems[$sku] = $oldQty + (float)$item['qty'];

            if ((float)$item['qty'] == 0) {
                continue;
            }

            if ($this->isEnableInventoryMovement()) {
                $movement = [
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::ACTION_TYPE => self::MOVEMENT_MAGESTORE_ACTION_TYPE,
                    InventoryMovementInterface::SOURCE_CODE => $transferInventory->getDesWarehouseCode(),
                    InventoryMovementInterface::IS_IN_STOCK => ($receiveItems[$sku] > 0) ? 1 : 0,
                    InventoryMovementInterface::PRODUCT_SKU => $sku,
                    InventoryMovementInterface::OLD_QTY => $oldQty,
                    InventoryMovementInterface::CHANGE_QTY => (float)$item['qty'],
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate()
                ];
                if (count($metaData)) {
                    $movement[InventoryMovementInterface::METADATA] = $metaData;
                }
                if ($currentUser && $currentUser->getUserId()) {
                    $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                    $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                    $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                }
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();

                $movementsData[] = $movement;
            }
        }

        $this->processChangeQuantity($transferInventory->getDesWarehouseCode(), $receiveItems, $movementsData);
        return true;
    }

    /**
     * Update Quantity In Transfer Stock
     *
     * @param int $transferId
     * @param array $receiveItemsData
     * @param float $totalReceiveQty
     */
    public function updateQuantityInTransferStock(int $transferId, array $receiveItemsData, float $totalReceiveQty)
    {
        $items = $this->getProductFromInventoryTransfer($transferId);
        $numberOfReceived = $this->inventoryReceiveProductCollectionFactory->create()->getNumberProductSkuReceive(
            $transferId
        );
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($transferId);

        $transferInventory->setQtyReceived($transferInventory->getQtyReceived() + $totalReceiveQty);
        if ($transferInventory->getQtyReceived() >= $transferInventory->getQtyTransferred()
            && $numberOfReceived >= $items->getSize()) {
            $transferInventory->setStatus(Status::STATUS_CLOSED);
            $transferInventory->setStage(Stage::STAGE_COMPLETED);
        } else {
            $transferInventory->setStage(Stage::STAGE_RECEIVING);
        }
        $transferInventory->save();

        foreach ($items as $item) {
            if (isset($receiveItemsData[$item->getProductSku()])) {
                $item->setQtyReceived(
                    $item->getQtyReceived() + $receiveItemsData[$item->getProductSku()]['qty']
                )->save();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function receiveAllProducts($transferId)
    {
        $items = $this->getProductFromInventoryTransfer($transferId);

        $dataReceive = [];
        foreach ($items as $item) {
            $dataReceive[] = [
                'product_id' => $item->getProductId(),
                'product_sku' => $item->getProductSku(),
                'product_name' => $item->getProductName(),
                'qty' => $item->getQtyTransferred() - $item->getQtyReceived()
            ];
        }

        return $this->receiveProducts($transferId, $dataReceive);
    }

    /**
     * Is Enable Inventory Movement
     *
     * @return bool
     */
    public function isEnableInventoryMovement()
    {
        $moduleManager = $this->_objectManager->get(ModuleManager::class);
        return $moduleManager->isEnabled('Magestore_InventoryMovementApi');
    }
}
