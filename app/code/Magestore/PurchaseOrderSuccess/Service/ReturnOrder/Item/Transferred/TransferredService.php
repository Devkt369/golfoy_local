<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PurchaseOrderSuccess\Service\ReturnOrder\Item\Transferred;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validation\ValidationException;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\PurchaseOrderSuccess\Api\ReturnOrderRepositoryInterface;
use Magestore\PurchaseOrderSuccess\Api\ReturnOrderItemRepositoryInterface;
use Magestore\PurchaseOrderSuccess\Api\ReturnOrderItemTransferredRepositoryInterface;
use Magestore\PurchaseOrderSuccess\Api\Data\ReturnOrderInterface;
use Magestore\PurchaseOrderSuccess\Api\Data\ReturnOrderItemInterface;
use Magestore\PurchaseOrderSuccess\Model\ReturnOrder\Item\Transferred;
use Magestore\PurchaseOrderSuccess\Model\ReturnOrder\Item\TransferredFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;

/**
 * Transferred Service Of Return Request
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransferredService
{
    const MOVEMENT_RETURN_REQUEST_ACTION = 'return_request';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ReturnOrderRepositoryInterface
     */
    protected $returnOrderRepository;

    /**
     * @var ReturnOrderItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var ReturnOrderItemTransferredRepositoryInterface
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
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var GetCurrentUserInterface
     */
    private $getCurrentUser;
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * TransferredService constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param ReturnOrderRepositoryInterface $returnOrderRepository
     * @param ReturnOrderItemRepositoryInterface $itemRepository
     * @param ReturnOrderItemTransferredRepositoryInterface $transferredRepository
     * @param TransferredFactory $transferredFactory
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param DateTime $dateTime
     * @param GetCurrentUserInterface $getCurrentUser
     * @param EventManager $eventManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ReturnOrderRepositoryInterface $returnOrderRepository,
        ReturnOrderItemRepositoryInterface $itemRepository,
        ReturnOrderItemTransferredRepositoryInterface $transferredRepository,
        TransferredFactory $transferredFactory,
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        SourceItemsSaveInterface $sourceItemsSave,
        DateTime $dateTime,
        GetCurrentUserInterface $getCurrentUser,
        EventManager $eventManager
    ) {
        $this->objectManager = $objectManager;
        $this->returnOrderRepository = $returnOrderRepository;
        $this->itemRepository = $itemRepository;
        $this->transferredRepository = $transferredRepository;
        $this->transferredFactory = $transferredFactory;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->dateTime = $dateTime;
        $this->getCurrentUser = $getCurrentUser;
        $this->eventManager = $eventManager;
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
     * Set Qty Transferred
     *
     * @param ReturnOrderItemInterface $returnItem
     * @param array $transferData
     * @return array|mixed
     */
    public function setQtyTransferred(ReturnOrderItemInterface $returnItem, $transferData = [])
    {
        $qty = $returnItem->getQtyReturned() - $returnItem->getQtyTransferred();
        if (!isset($transferData['transferred_qty']) || $transferData['transferred_qty'] > $qty) {
            $transferData['transferred_qty'] = $qty;
        }
        return $transferData;
    }

    /**
     * Prepare Item Transferred
     *
     * @param ReturnOrderItemInterface $returnItem
     * @param array $transferData
     * @param array $params
     * @param string|null $createdBy
     * @return Transferred
     */
    public function prepareItemTransferred(
        ReturnOrderItemInterface $returnItem,
        $transferData = [],
        $params = [],
        $createdBy = null
    ) {
        $transferData = $this->setQtyTransferred($returnItem, $transferData);
        return $this->transferredFactory->create()
            ->setReturnItemId($returnItem->getReturnItemId())
            ->setQtyTransferred($transferData['transferred_qty'])
            ->setTransferredAt($params['transferred_at'])
            ->setCreatedBy($createdBy);
    }

    /**
     * Transfer Item
     *
     * @param ReturnOrderInterface $returnOrder
     * @param ReturnOrderItemInterface $returnItem
     * @param array $transferData
     * @param array $params
     * @param string|null $createdBy
     * @return bool|Transferred
     */
    public function transferItem(
        ReturnOrderInterface $returnOrder,
        ReturnOrderItemInterface $returnItem,
        $transferData = [],
        $params = [],
        $createdBy = null
    ) {
        $transferData = $this->setQtyTransferred($returnItem, $transferData);
        $itemTransferred = $this->prepareItemTransferred($returnItem, $transferData, $params, $createdBy);
        try {
            $this->transferredRepository->save($itemTransferred);
            $returnItem->setQtyTransferred(
                $returnItem->getQtyTransferred() + $transferData['transferred_qty']
            );
            $this->itemRepository->save($returnItem);
            $returnOrder->setTotalQtyTransferred(
                $returnOrder->getTotalQtyTransferred() + $transferData['transferred_qty']
            );
        } catch (Exception $e) {
            return false;
        }
        return $transferData;
    }

    /**
     * Subtract Stock From Catalog
     *
     * @param array $transferredItems
     * @param array $params
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function subtractStockFromCatalog($transferredItems, $params)
    {
        $sourceItems = [];
        $movementsData = [];
        if ($this->isEnableInventoryMovement()) {
            /** @var ReturnOrderInterface $returnRequest */
            $returnRequest = $this->returnOrderRepository->get($params[ReturnOrderInterface::RETURN_ORDER_ID]);
            $metaData = [
                InventoryMovementInterface::ACTION_LABEL => __('New Return Request No'),
                InventoryMovementInterface::OBJECT_TYPE => self::MOVEMENT_RETURN_REQUEST_ACTION,
                InventoryMovementInterface::OBJECT_ID => $returnRequest->getReturnOrderId(),
                InventoryMovementInterface::OBJECT_INCREMENT_ID => $returnRequest->getReturnCode()
            ];
            $currentUser = $this->getCurrentUser->getCurrentUser();
        }

        foreach ($transferredItems as $productSku => $qtyTransferred) {
            if (!$qtyTransferred) {
                continue;
            }

            // get source item of transferred items
            try {
                $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($params['warehouse_id'], $productSku);
            } catch (NoSuchEntityException $e) {
                throw $e;
            }

            $oldQty = $sourceItem->getQuantity();
            $sourceItem->setQuantity($sourceItem->getQuantity() - $qtyTransferred);
            $sourceItems[$productSku] = $sourceItem;

            if ($this->isEnableInventoryMovement()) {
                $movement = [
                    InventoryMovementInterface::ACTION_TYPE => self::MOVEMENT_RETURN_REQUEST_ACTION,
                    InventoryMovementInterface::SOURCE_CODE => $params['warehouse_id'],
                    InventoryMovementInterface::IS_IN_STOCK => $sourceItem->getStatus(),
                    InventoryMovementInterface::PRODUCT_SKU => $productSku,
                    InventoryMovementInterface::OLD_QTY => $oldQty,
                    InventoryMovementInterface::CHANGE_QTY => -$qtyTransferred,
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
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
                    . $this->dateTime->timestamp();

                $movementsData[$productSku] = $movement;
            }
        }

        if ($this->isEnableInventoryMovement()) {
            /* Save log before processing */
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);

            if (count($movementsData)) {
                $this->eventManager->dispatch('inventory_movement_log_skip_default');
            }
        }

        if (!empty($sourceItems)) {
            $this->sourceItemsSave->execute($sourceItems);
        }

        if ($this->isEnableInventoryMovement()) {
            /* Save log after processing */
            foreach (array_keys($movementsData) as $key) {
                $movementsData[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
                $movementsData[$key][InventoryMovementInterface::NEW_QTY] = $sourceItems[$key]->getQuantity();
            }
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);
        }
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
