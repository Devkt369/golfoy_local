<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PurchaseOrderSuccess\Service\PurchaseOrder\Item\Transferred;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\PurchaseOrderSuccess\Api\Data\PurchaseOrderInterface;
use Magestore\PurchaseOrderSuccess\Api\Data\PurchaseOrderItemInterface;
use Magestore\PurchaseOrderSuccess\Api\MultiSourceInventory\SourceManagementInterface;
use Magestore\PurchaseOrderSuccess\Api\MultiSourceInventory\StockManagementInterface;
use Magestore\PurchaseOrderSuccess\Api\PurchaseOrderItemRepositoryInterface;
use Magestore\PurchaseOrderSuccess\Api\PurchaseOrderItemTransferredRepositoryInterface;
use Magestore\PurchaseOrderSuccess\Api\PurchaseOrderRepositoryInterface;
use Magestore\PurchaseOrderSuccess\Model\PurchaseOrder\Item\Transferred;
use Magestore\PurchaseOrderSuccess\Model\PurchaseOrder\Item\TransferredFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magestore\PurchaseOrderSuccess\Model\Inventory\ChangeParentStockStatus;
use Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus as MagentoChangeParentStockStatus;

/**
 * Service Transfer PO Items
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransferredService
{
    const MOVEMENT_PURCHASE_ORDER_TYPE = 'purchase_order';
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    protected $purchaseOrderRepository;

    /**
     * @var PurchaseOrderItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var PurchaseOrderItemReturnedRepositoryInterface
     */
    protected $transferredRepository;

    /**
     * @var TransferredFactory
     */
    protected $transferredFactory;
    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    private $getSourceItemBySourceCodeAndSku;
    /**
     * @var StockManagementInterface
     */
    private $stockManagement;
    /**
     * @var SourceManagementInterface
     */
    private $sourceManagement;
    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * TransferredService constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param PurchaseOrderItemRepositoryInterface $itemRepository
     * @param PurchaseOrderItemTransferredRepositoryInterface $transferredRepository
     * @param TransferredFactory $transferredFactory
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param StockManagementInterface $stockManagement
     * @param SourceManagementInterface $sourceManagement
     * @param SourceItemsSaveInterface $sourceItemsSave
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        PurchaseOrderItemRepositoryInterface $itemRepository,
        PurchaseOrderItemTransferredRepositoryInterface $transferredRepository,
        TransferredFactory $transferredFactory,
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        StockManagementInterface $stockManagement,
        SourceManagementInterface $sourceManagement,
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        $this->objectManager = $objectManager;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->itemRepository = $itemRepository;
        $this->transferredRepository = $transferredRepository;
        $this->transferredFactory = $transferredFactory;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->stockManagement = $stockManagement;
        $this->sourceManagement = $sourceManagement;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    /**
     * Process Transferred Data
     *
     * @param array $params
     * @return array
     */
    public function processTransferredData($params = [])
    {
        $result = [];
        foreach ($params as $item) {
            if (isset($item['transferred_qty']) && $item['transferred_qty'] > 0) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }

    /**
     * Update Stock
     *
     * @param array $transferredData
     * @param array $params
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateStock(array $transferredData, array $params)
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->objectManager->get(EventManager::class);
        $eventManager->dispatch('inventory_movement_log_skip_default');

        $sourceItems = [];
        $movementsData = [];

        if ($this->isEnableInventoryMovement()) {
            /** @var PurchaseOrderInterface $purchaseOrder */
            $purchaseOrder = $this->purchaseOrderRepository->get($params[PurchaseOrderInterface::PURCHASE_ORDER_ID]);
            $metaData = [
                InventoryMovementInterface::ACTION_LABEL => __('New Purchase Order No'),
                InventoryMovementInterface::OBJECT_TYPE => self::MOVEMENT_PURCHASE_ORDER_TYPE,
                InventoryMovementInterface::OBJECT_ID => $purchaseOrder->getPurchaseOrderId(),
                InventoryMovementInterface::OBJECT_INCREMENT_ID => $purchaseOrder->getPurchaseCode()
            ];

            /** @var GetCurrentUserInterface $getCurrentUser */
            $getCurrentUser = $this->objectManager->get(GetCurrentUserInterface::class);
            $currentUser = $getCurrentUser->getCurrentUser();

            /** @var DateTime $dateTime */
            $dateTime = $this->objectManager->get(DateTime::class);
        }

        foreach ($transferredData as $data) {
            if (!$data['transferred_qty'] || !$data['product_sku'] || !$params['warehouse_id']) {
                continue;
            }

            // get source item of transferred items
            try {
                $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute(
                    $params['warehouse_id'],
                    $data['product_sku']
                );
            } catch (NoSuchEntityException $e) {
                // add product to source if it doesn't exist in source
                $this->stockManagement->createSourceItem($data['product_sku'], $params['warehouse_id']);

                // get source item again
                $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute(
                    $params['warehouse_id'],
                    $data['product_sku']
                );
            }

            $oldQty = $sourceItem->getQuantity();
            $sourceItem->setQuantity($oldQty + $data['transferred_qty']);
            $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
            $sourceItems[$data['product_sku']] = $sourceItem;

            if ($this->isEnableInventoryMovement()) {
                $movement = [
                    InventoryMovementInterface::ACTION_TYPE => self::MOVEMENT_PURCHASE_ORDER_TYPE,
                    InventoryMovementInterface::SOURCE_CODE => $params['warehouse_id'],
                    InventoryMovementInterface::IS_IN_STOCK => $sourceItem->getStatus(),
                    InventoryMovementInterface::PRODUCT_SKU => $data['product_sku'],
                    InventoryMovementInterface::OLD_QTY => $oldQty,
                    InventoryMovementInterface::CHANGE_QTY => $data['transferred_qty'],
                    InventoryMovementInterface::CREATED_AT => $dateTime->gmtDate(),
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL
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
                    . $dateTime->timestamp();

                $movementsData[] = $movement;
            }
        }

        if ($this->isEnableInventoryMovement()) {
            /* Save log before processing */
            $eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);

            if (count($movementsData)) {
                $eventManager->dispatch('inventory_movement_log_skip_default');
            }
        }

        if (!empty($sourceItems)) {
            $this->sourceItemsSave->execute($sourceItems);
        }

        /* Update parent stock status for Legacy stock item of Magento */
        $productIds = array_unique(array_column($transferredData, 'id'));
        if (class_exists(MagentoChangeParentStockStatus::class)) {
            /** @var MagentoChangeParentStockStatus $changeParentStockStatus */
            $changeParentStockStatus = $this->objectManager->get(MagentoChangeParentStockStatus::class);
        } else {
            /** @var ChangeParentStockStatus $changeParentStockStatus */
            $changeParentStockStatus = $this->objectManager->get(ChangeParentStockStatus::class);
        }
        $changeParentStockStatus->execute($productIds);
        /* End Update parent stock status for Legacy stock item of Magento */

        /* Save log after processing */
        if ($this->isEnableInventoryMovement()) {
            foreach (array_keys($movementsData) as $key) {
                $movementsData[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
                $sku = $movementsData[$key][InventoryMovementInterface::PRODUCT_SKU];
                $movementsData[$key][InventoryMovementInterface::NEW_QTY] = $sourceItems[$sku]->getQuantity();
            }
            $eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);
        }
    }

    /**
     * Set Qty Transferred
     *
     * @param PurchaseOrderItemInterface $purchaseItem
     * @param array $transferData
     * @return array|mixed
     */
    public function setQtyTransferred(PurchaseOrderItemInterface $purchaseItem, $transferData = [])
    {
        $qty = $purchaseItem->getQtyReceived() - $purchaseItem->getQtyTransferred() - $purchaseItem->getQtyReturned();
        if (!isset($transferData['transferred_qty']) || $transferData['transferred_qty'] > $qty) {
            $transferData['transferred_qty'] = $qty;
        }
        return $transferData;
    }

    /**
     * Prepare Item Transferred
     *
     * @param PurchaseOrderItemInterface $purchaseItem
     * @param array $transferData
     * @param array $params
     * @param string|null $createdBy
     * @return Transferred
     */
    public function prepareItemTransferred(
        PurchaseOrderItemInterface $purchaseItem,
        $transferData = [],
        $params = [],
        $createdBy = null
    ) {
        $transferData = $this->setQtyTransferred($purchaseItem, $transferData);
        return $this->transferredFactory->create()
            ->setPurchaseOrderItemId($purchaseItem->getPurchaseOrderItemId())
            ->setQtyTransferred($transferData['transferred_qty'])
            ->setWarehouseId($params['warehouse_id'])
            ->setTransferredAt($params['transferred_at'])
            ->setCreatedBy($createdBy)
            ->setPurchaseOrderItem($purchaseItem);
    }

    /**
     * Transfer Item
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param PurchaseOrderItemInterface $purchaseItem
     * @param array $transferData
     * @param array $params
     * @param string|null $createdBy
     * @return bool|Transferred
     */
    public function transferItem(
        PurchaseOrderInterface $purchaseOrder,
        PurchaseOrderItemInterface $purchaseItem,
        $transferData = [],
        $params = [],
        $createdBy = null
    ) {
        $transferData = $this->setQtyTransferred($purchaseItem, $transferData);
        $purchaseItem->setPurchaseOrder($purchaseOrder);
        $itemTransferred = $this->prepareItemTransferred($purchaseItem, $transferData, $params, $createdBy);
        try {
            $this->transferredRepository->save($itemTransferred);
            $purchaseItem->setQtyTransferred(
                $purchaseItem->getQtyTransferred() + $transferData['transferred_qty']
            );
            $this->itemRepository->save($purchaseItem);
            $purchaseOrder->setTotalQtyTransferred(
                $purchaseOrder->getTotalQtyTransferred() + $transferData['transferred_qty']
            );
        } catch (Exception $e) {
            return false;
        }
        return $transferData;
    }

    /**
     * Is Enable Inventory Movement
     *
     * @return bool
     */
    public function isEnableInventoryMovement()
    {
        $moduleManager = $this->objectManager->get(ModuleManager::class);
        return $moduleManager->isEnabled('Magestore_InventoryMovementApi');
    }
}
