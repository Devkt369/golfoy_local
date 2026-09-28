<?php

namespace Magestore\BranchRequest\Model\InventoryTransfer;

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
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterfaceFactory;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferProductInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferProductInterfaceFactory;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\DeliveryInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\DeliveryInterfaceFactory;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\DeliveryProductInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\DeliveryProductInterfaceFactory;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\ReceiveInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\ReceiveInterfaceFactory;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\ReceiveProductInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\ReceiveProductInterfaceFactory;
use Magestore\BranchRequest\Api\MultiSourceInventory\SourceManagementInterface;
use Magestore\BranchRequest\Api\TransferManagementInterface;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\Collection;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\CollectionFactory;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct\CollectionFactory
    as ProductCollectionFactory;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\GlobalStock\Collection as GlobalStockCollection;
use Magento\Framework\Serialize\Serializer\Json as JsonSerialize;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magestore\BranchRequest\Service\BranchRequestService;
use Psr\Log\LoggerInterface;

/**
 * Service Transfer Management
 *
 * @SuppressWarnings(PHPMD)
 */
class TransferManagement implements TransferManagementInterface
{
    const MOVEMENT_MAGESTORE_ACTION_TYPE_DELIVERY = 'magestore_branchrequest_delivery';
    const MOVEMENT_MAGESTORE_ACTION_TYPE_RECEIVE = 'magestore_branchrequest_receive';
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
     * @var DeliveryInterfaceFactory
     */
    protected $deliveryInterfaceFactory;
    /**
     * @var DeliveryProductInterfaceFactory
     */
    protected $deliveryProductInterfaceFactory;
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
     * @var BranchRequestService
     */
    protected $branchRequestService;

    /**
     * TransferManagement constructor
     *
     * @param InventoryTransferInterfaceFactory $inventoryTransferFactory
     * @param SourceItemConvert $sourceItemConvert
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param ProductCollectionFactory $inventoryTransferProductCollectionFactory
     * @param CollectionFactory $inventoryReceiveProductCollectionFactory
     * @param SourceManagementInterface $sourceManagement
     * @param InventoryTransferProductInterfaceFactory $inventoryTransferProductFactory
     * @param ObjectManagerInterface $objectManager
     * @param DeliveryInterfaceFactory $deliveryInterfaceFactory
     * @param DeliveryProductInterfaceFactory $deliveryProductInterfaceFactory
     * @param ReceiveInterfaceFactory $receiveInterfaceFactory
     * @param ReceiveProductInterfaceFactory $receiveProductInterfaceFactory
     * @param Session $authSession
     * @param Type $type
     * @param EventManager $eventManager
     * @param GetCurrentUserInterface $getCurrentUser
     * @param DateTime $dateTime
     * @param BranchRequestService $branchRequestService
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        InventoryTransferInterfaceFactory        $inventoryTransferFactory,
        SourceItemConvert                        $sourceItemConvert,
        SourceItemsSaveInterface                 $sourceItemsSave,
        ProductCollectionFactory                 $inventoryTransferProductCollectionFactory,
        CollectionFactory                        $inventoryReceiveProductCollectionFactory,
        SourceManagementInterface                $sourceManagement,
        InventoryTransferProductInterfaceFactory $inventoryTransferProductFactory,
        ObjectManagerInterface                   $objectManager,
        DeliveryInterfaceFactory                 $deliveryInterfaceFactory,
        DeliveryProductInterfaceFactory          $deliveryProductInterfaceFactory,
        ReceiveInterfaceFactory                  $receiveInterfaceFactory,
        ReceiveProductInterfaceFactory           $receiveProductInterfaceFactory,
        Session                                  $authSession,
        Type                                     $type,
        EventManager                             $eventManager,
        GetCurrentUserInterface                  $getCurrentUser,
        DateTime                                 $dateTime,
        BranchRequestService                     $branchRequestService
    ) {
        $this->inventoryTransferFactory = $inventoryTransferFactory;
        $this->sourceItemConvert = $sourceItemConvert;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->inventoryTransferProductCollectionFactory = $inventoryTransferProductCollectionFactory;
        $this->sourceManagement = $sourceManagement;
        $this->inventoryTransferProductFactory = $inventoryTransferProductFactory;
        $this->_objectManager = $objectManager;
        $this->deliveryInterfaceFactory = $deliveryInterfaceFactory;
        $this->deliveryProductInterfaceFactory = $deliveryProductInterfaceFactory;
        $this->receiveInterfaceFactory = $receiveInterfaceFactory;
        $this->receiveProductInterfaceFactory = $receiveProductInterfaceFactory;
        $this->authSession = $authSession;
        $this->type = $type;
        $this->inventoryReceiveProductCollectionFactory = $inventoryReceiveProductCollectionFactory;
        $this->eventManager = $eventManager;
        $this->getCurrentUser = $getCurrentUser;
        $this->dateTime = $dateTime;
        $this->branchRequestService = $branchRequestService;
    }

    /**
     * Start Branch Request by transfer id
     *
     * @param int $transferId
     * @return array
     */
    public function startBranchRequest($transferId)
    {
        $result = [
            'status' => false
        ];
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($transferId);
        if (!$transferInventory->getInventorytransferId()) {
            $result['message'] = __('Could not find branch request with ID %1', $transferId);
            return $result;
        } elseif ($transferInventory->getStatus() != InventoryTransferInterface::STATUS_NEW) {
            $result['message'] = __('The branch request has already started.');
            return $result;
        }

        $items = $this->getProductFromInventoryTransfer($transferId);
        if (!count($items)) {
            $result['message'] = __('Please select item(s) before start request.');
            return $result;
        }

        $transferInventory->setStatus(InventoryTransferInterface::STATUS_WAIT_APPROVE)->save();

        $result = [
            'status' => true,
            'message' => __('The branch request has been started successfully.')
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
                'qty_requested' => isset($item['qty_requested']) ? $item['qty_requested'] : 0,
                'qty_delivered' => isset($item['qty_delivered']) ? $item['qty_delivered'] : 0,
                'qty_received' => isset($item['qty_received']) ? $item['qty_received'] : 0
            ];
            $transferProduct->setData($data)->save();
            $totalQtyToTransfer += (float)$item['qty_requested'];
        }

        $transferInventory->setQtyRequested($totalQtyToTransfer)->save();

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
        $collection->getSelect()->where('((qty_requested - qty_received > 0) OR (qty_requested = 0))');
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
        $collection->getSelect()->where('((qty_requested - qty_received > 0) OR (qty_requested = 0))');
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
                $newQtyToTransfer = isset($item['qty_requested']) ? $item['qty_requested'] : 0;
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
                'qty_requested' => isset($item['qty_requested']) ? $item['qty_requested'] : 0,
                'qty_received' => isset($item['qty_received']) ? $item['qty_received'] : 0
            ];
            $transferProduct->setData($data)->save();

            $totalQtyToTransfer += (float)$item['qty_requested'];
        }

        $transferInventory->setQtyTransferred($totalQtyToTransfer)->save();

        return true;
    }

    /**
     * Delivery products by transfer id and items
     *
     * @param int $transferId
     * @param array $general
     * @param array $items
     * @return bool
     */
    public function deliveryProducts($transferId, $general, $items)
    {
        $deliveryItemsData = [];
        $totalDeliveryQty = 0;

        foreach ($items as $item) {
            $totalDeliveryQty += $item['qty'];
            $deliveryItemsData[$item['product_sku']] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'],
                'avail_qty' => $item['avail_qty'],
                'qty' => $item['qty']
            ];
        }

        try {
            /** @var DeliveryInterface $delivery */
            $delivery = $this->deliveryInterfaceFactory->create();
            $delivery->setCreatedById($this->authSession->getUser()->getId());
            $delivery->setDeliveryCode($this->branchRequestService->generateCode('delivery'));
            $delivery->setCreatedByName($this->authSession->getUser()->getUserName());
            $delivery->setInventorytransferId($transferId);
            $delivery->setTotalDeliveryQty($totalDeliveryQty);
            $delivery->setNote($general['note']);
            $delivery->setRemarks($general['remarks']);
            $delivery->save();

            foreach ($deliveryItemsData as $itemData) {
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->_objectManager->create(
                    \Magento\Catalog\Model\Product::class
                )->load($itemData['product_id']);
                /** @var  DeliveryProductInterface $deliveryItem */
                $deliveryItem = $this->deliveryProductInterfaceFactory->create();
                $deliveryItem->setData($itemData);
                $deliveryItem->setDeliveryId($delivery->getId());
                $deliveryItem->setProductPrice($product->getPriceInfo()->getPrice('regular_price')->getValue());
                $deliveryItem->save();
            }

            $this->deductProductQtyInFromSource($delivery);

            $this->updateQtyDeliveredInTransferStock($delivery);
        } catch (\Exception $exception) {
            $this->log("---delivery error log---");
            $this->log($exception->getMessage());
            $this->log($transferId);
            $this->log(json_encode($general));
            $this->log(json_encode($items));
            $this->log("---end delivery error log---");
            return false;
        }

        return true;
    }

    /**
     * Deduct Product Qty In From Source
     *
     * @param DeliveryInterface $delivery
     * @return bool
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     * @SuppressWarnings(PHPMD)
     */
    public function deductProductQtyInFromSource(DeliveryInterface $delivery)
    {
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($delivery->getInventorytransferId());
        $fromSource = $transferInventory->getData(InventoryTransferInterface::FROM);

        $deliveryItems = [];
        $movementsData = [];

        if ($this->isEnableInventoryMovement()) {
            $currentUser = $this->getCurrentUser->getCurrentUser();
            $metaData = [
                InventoryMovementInterface::ACTION_LABEL => __('New Delivery Note No'),
                InventoryMovementInterface::OBJECT_TYPE => self::MOVEMENT_MAGESTORE_ACTION_TYPE_DELIVERY,
                InventoryMovementInterface::OBJECT_ID => $delivery->getId(),
                InventoryMovementInterface::OBJECT_INCREMENT_ID => $delivery->getDeliveryCode()
            ];
        }

        /** @var DeliveryProductInterface $deliveryItem */
        foreach ($delivery->getDeliveryItems() as $deliveryItem) {
            $deliveryQty = (float)$deliveryItem->getData(DeliveryProductInterface::QTY);
            if ($deliveryQty <= 0) {
                continue;
            }
            $sku = $deliveryItem->getData(DeliveryProductInterface::PRODUCT_SKU);
            $sourceItems = $this->sourceManagement->getSourceItemsMap($sku, [$fromSource]);
            $oldQty = isset($sourceItems[$fromSource])
                ? $sourceItems[$fromSource]->getQuantity()
                : 0;
            $deliveryItems[$sku] = $oldQty - $deliveryQty;

            if ($this->isEnableInventoryMovement()) {
                $movement = [
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::ACTION_TYPE => self::MOVEMENT_MAGESTORE_ACTION_TYPE_DELIVERY,
                    InventoryMovementInterface::SOURCE_CODE => $fromSource,
                    InventoryMovementInterface::IS_IN_STOCK => ($deliveryItems[$sku] > 0) ? 1 : 0,
                    InventoryMovementInterface::PRODUCT_SKU => $sku,
                    InventoryMovementInterface::OLD_QTY => $oldQty,
                    InventoryMovementInterface::CHANGE_QTY => -$deliveryQty,
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

        $this->processChangeQuantity($fromSource, $deliveryItems, $movementsData);
        return true;
    }

    /**
     * Update Qty Delivered In Transfer Stock
     *
     * @param DeliveryInterface $delivery
     * @return void
     * @throws \Exception
     */
    public function updateQtyDeliveredInTransferStock(DeliveryInterface $delivery)
    {
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($delivery->getInventorytransferId());
        $qtyDelivered = min(
            $transferInventory->getQtyRequested(),
            $transferInventory->getQtyDelivered() + $delivery->getTotalDeliveryQty()
        );
        $transferInventory->setQtyDelivered($qtyDelivered);
        if ($transferInventory->getStatus() != InventoryTransferInterface::STATUS_PROCESSING) {
            $transferInventory->setStatus(InventoryTransferInterface::STATUS_PROCESSING);
        }
        $transferInventory->save();
        $deliveryItems = $delivery->getDeliveryItems();
        /** @var DeliveryProduct $item */
        foreach ($deliveryItems as $item) {
            /** @var InventoryTransferProduct $transferItem */
            $transferItem = $item->getTransferItem();
            if ($transferItem->getId()) {
                $qtyDeliveredItem = min(
                    $transferItem->getQtyRequested(),
                    $transferItem->getQtyDelivered() + $item->getQty()
                );
                $transferItem->setQtyDelivered($qtyDeliveredItem)->save();
            } else {
                $this->log(__('Cant find transfer item for delivery item %1', $item->getId()));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function receiveProducts($transferId, $general, $items)
    {
        try {
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
            $receive->setReceiveCode($this->branchRequestService->generateCode('receive'));
            $receive->setCreatedById($this->authSession->getUser()->getId());
            $receive->setCreatedByName($this->authSession->getUser()->getUserName());
            $receive->setInventorytransferId($transferId);
            $receive->setTotalReceiveQty($totalReceiveQty);
            $receive->setNote($general['note']);
            $receive->setRemarks($general['remarks']);
            $receive->save();
            foreach ($receiveItemsData as $itemData) {
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->_objectManager->create(
                    \Magento\Catalog\Model\Product::class
                )->load($itemData['product_id']);
                /** @var ReceiveProductInterface $receiveItem */
                $receiveItem = $this->receiveProductInterfaceFactory->create();
                $receiveItem->setData($itemData);
                $receiveItem->setReceiveId($receive->getReceiveId());
                $receiveItem->setProductPrice($product->getPriceInfo()->getPrice('regular_price')->getValue());
                $receiveItem->save();
            }
            $this->increaseProductQtyInDestinationSource($receive);

            $this->updateQtyReceivedInTransferStock($receive);

        } catch (\Exception $exception) {
            $this->log("---receive error log---");
            $this->log($exception->getMessage());
            $this->log($transferId);
            $this->log(json_encode($general));
            $this->log(json_encode($items));
            $this->log("---end receive error log---");
            return false;
        }

        return true;
    }

    /**
     * Increase Product Qty In DestinationSource
     *
     * @param Receive $receive
     * @return bool
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     * @SuppressWarnings(PHPMD)
     */
    public function increaseProductQtyInDestinationSource(Receive $receive)
    {
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($receive->getInventorytransferId());
        $sourceCode = $transferInventory->getDesWarehouseCode();
        $actionLabel = __('New Receipt Note No');
        $objectType = self::MOVEMENT_MAGESTORE_ACTION_TYPE_RECEIVE;
        $objectId = $receive->getReceiveId();
        $objectIncrementId = $receive->getReceiveCode();

        $qtyItems = [];
        $movementsData = [];
        if ($this->isEnableInventoryMovement()) {
            $currentUser = $this->getCurrentUser->getCurrentUser();
            $metaData = [
                InventoryMovementInterface::ACTION_LABEL => $actionLabel,
                InventoryMovementInterface::OBJECT_TYPE => $objectType,
                InventoryMovementInterface::OBJECT_ID => $objectId,
                InventoryMovementInterface::OBJECT_INCREMENT_ID => $objectIncrementId
            ];
        }

        foreach ($receive->getReceiveItems() as $receiveItem) {
            $sku = $receiveItem->getProductSku();
            $qty = (float)$receiveItem->getQty();
            if ($qty == 0) {
                continue;
            }
            $sourceItems = $this->sourceManagement->getSourceItemsMap(
                $sku,
                [$sourceCode]
            );
            $oldQty = isset($sourceItems[$sourceCode])
                ? $sourceItems[$sourceCode]->getQuantity()
                : 0;
            $qtyItems[$sku] = $oldQty + $qty;

            if ($this->isEnableInventoryMovement()) {
                $movement = [
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::ACTION_TYPE => $objectType,
                    InventoryMovementInterface::SOURCE_CODE => $sourceCode,
                    InventoryMovementInterface::IS_IN_STOCK => ($qtyItems[$sku] > 0) ? 1 : 0,
                    InventoryMovementInterface::PRODUCT_SKU => $sku,
                    InventoryMovementInterface::OLD_QTY => $oldQty,
                    InventoryMovementInterface::CHANGE_QTY => $qty,
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

        $this->processChangeQuantity($sourceCode, $qtyItems, $movementsData);
        return true;
    }

    /**
     * Update Qty Received In Transfer Stock
     *
     * @param Receive $receive
     * @return void
     * @throws \Exception
     */
    public function updateQtyReceivedInTransferStock(Receive $receive)
    {
        /** @var InventoryTransferInterface $transferInventory */
        $transferInventory = $this->inventoryTransferFactory->create()->load($receive->getInventorytransferId());
        $qtyReceived = min(
            $transferInventory->getQtyDelivered(),
            $transferInventory->getQtyReceived() + $receive->getTotalReceiveQty()
        );
        $transferInventory->setQtyReceived($qtyReceived);
        if ($transferInventory->getQtyReceived() == $transferInventory->getQtyRequested()) {
            $transferInventory->setStatus(InventoryTransferInterface::STATUS_CLOSED);
        }
        $transferInventory->save();
        /** @var ReceiveProduct $item */
        foreach ($receive->getReceiveItems() as $item) {
            /** @var InventoryTransferProduct $transferItem */
            $transferItem = $item->getTransferItem();
            if ($transferItem->getId()) {
                $qtyReceiveItem = min(
                    $transferItem->getQtyDelivered(),
                    $transferItem->getQtyReceived() + $item->getQty()
                );
                $transferItem->setQtyReceived($qtyReceiveItem)->save();
            } else {
                $this->log(__('Cant find transfer item for receive item %1', $item->getId()));
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

        return $this->receiveProducts($transferId, [], $dataReceive);
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

    /**
     * Log
     *
     * @param mixed $message
     * @return $this
     */
    public function log($message)
    {
        /** @var LoggerInterface $logger */
        $logger = $this->_objectManager->get(LoggerInterface::class);
        $logger->error($message);
        return $this;
    }
}
