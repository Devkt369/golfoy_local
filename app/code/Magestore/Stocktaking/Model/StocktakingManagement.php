<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types = 1);

namespace Magestore\Stocktaking\Model;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magestore\Core\Api\Data\CurrentUserInterface;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\Stocktaking\Api\StocktakingArchiveItemRepositoryInterface;
use Magestore\Stocktaking\Model\ResourceModel\Stocktaking\SourceProduct\Collection
    as SourceProductCollection;
use Magestore\Stocktaking\Model\ResourceModel\Stocktaking\SourceProduct\CollectionFactory
    as SourceProductCollectionFactory;
use Magestore\Stocktaking\Api\StocktakingItemRepositoryInterface;
use Magestore\Stocktaking\Api\Data\StocktakingInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\InventoryImportExport\Model\Import\SourceItemConvert;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryImportExport\Model\Import\Sources;
use Magestore\Stocktaking\Model\ResourceModel\Stocktaking\StocktakingItem\Collection
    as StocktakingItemCollection;
use Magestore\Stocktaking\Model\ResourceModel\Stocktaking as StocktakingResourceModel;

/**
 * StocktakingManagement: Process stocktaking
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StocktakingManagement implements \Magestore\Stocktaking\Api\StocktakingManagementInterface
{
    /**
     * Stock movement action type for stock staking
     */
    const ACTION_TYPE_STOCK_TAKING = 'stock_taking';

    /**
     * Stock movement object type for stock staking
     */
    const OBJECT_TYPE_STOCK_TAKING = 'stock_taking';

    /**
     * @var SourceProductCollectionFactory
     */
    protected $sourceProductCollectionFactory;
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var StocktakingItemRepositoryInterface
     */
    protected $stocktakingItemRepository;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var SourceItemConvert
     */
    protected $sourceItemConvert;
    /**
     * @var SourceItemsSaveInterface
     */
    protected $sourceItemsSave;

    /**
     * @var StocktakingArchiveItemRepositoryInterface
     */
    protected $stocktakingArchiveItemRepository;

    /**
     * @var StocktakingResourceModel
     */
    protected $stocktakingResourceModel;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * StocktakingManagement constructor.
     * @param SourceProductCollectionFactory $sourceProductCollectionFactory
     * @param SerializerInterface $serializer
     * @param StocktakingItemRepositoryInterface $stocktakingItemRepository
     * @param EventManagerInterface $eventManager
     * @param SourceItemConvert $sourceItemConvert
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param StocktakingArchiveItemRepositoryInterface $stocktakingArchiveItemRepository
     * @param StocktakingResourceModel $stocktakingResourceModel
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        SourceProductCollectionFactory $sourceProductCollectionFactory,
        SerializerInterface $serializer,
        StocktakingItemRepositoryInterface $stocktakingItemRepository,
        EventManagerInterface $eventManager,
        SourceItemConvert $sourceItemConvert,
        SourceItemsSaveInterface $sourceItemsSave,
        StocktakingArchiveItemRepositoryInterface $stocktakingArchiveItemRepository,
        StocktakingResourceModel $stocktakingResourceModel,
        ObjectManagerInterface $objectManager
    ) {
        $this->sourceProductCollectionFactory = $sourceProductCollectionFactory;
        $this->serializer = $serializer;
        $this->stocktakingItemRepository = $stocktakingItemRepository;
        $this->eventManager = $eventManager;
        $this->sourceItemConvert = $sourceItemConvert;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->stocktakingArchiveItemRepository = $stocktakingArchiveItemRepository;
        $this->stocktakingResourceModel = $stocktakingResourceModel;
        $this->objectManager = $objectManager;
    }

    /**
     * @inheritDoc
     */
    public function getSelectBarcodeProductListJson(array $productIds = [])
    {
        /** @var SourceProductCollection $collection */
        $collection = $this->sourceProductCollectionFactory->create();
        if (count($productIds)) {
            $collection->addFieldToFilter('entity_id', ['in' => array_values($productIds)]);
        }
        if (!$collection->isLoaded()) {
            $collection->load();
        }
        $items = $collection->toArray();
        return $this->convertProductBarcodeResultToJson($items);
    }

    /**
     * Get product json by barcode string
     *
     * @param string $barcode
     * @return string|bool
     */
    public function getProductJsonByBarcode(string $barcode)
    {
        /** @var SourceProductCollection $collection */
        $collection = $this->sourceProductCollectionFactory->create();
        $collection->getSelect()->where(
            $collection->getConnection()->prepareSqlCondition('barcode.barcode', ['finset' => $barcode])
        );
        $collection->setPageSize(1)->setCurPage(1);
        if (!$collection->isLoaded()) {
            $collection->load();
        }
        $items = $collection->toArray();
        return $this->convertProductBarcodeResultToJson($items);
    }

    /**
     * Convert product barcode result to json process
     *
     * @param array $items
     * @return bool|string
     */
    public function convertProductBarcodeResultToJson(array $items)
    {
        $result = [];
        foreach ($items as $item) {
            if (isset($item['quantity'])) {
                $item['quantity'] = (float)$item['quantity'];
            }
            if (isset($item['barcode'])) {
                $barcodes = explode(',', (string)$item['barcode']);
                foreach ($barcodes as $barcode) {
                    $result[$barcode] = $item;
                }
            }
        }

        return $this->serializer->serialize($result);
    }

    /**
     * @inheritDoc
     */
    public function addUncountedProductsToStocktaking(StocktakingInterface $stocktaking)
    {
        $stocktakingId = $stocktaking->getId();
        $uncountedProductSelect = $this->sourceProductCollectionFactory->create()
            ->getUncountedSkuStocktaking($stocktakingId)
            ->getSelect();
        $this->stocktakingResourceModel->addUncountedProductsToStocktaking($stocktaking, $uncountedProductSelect);
    }

    /**
     * @inheritDoc
     */
    public function getStocktakingItemsToCompleteSelect(StocktakingInterface $stocktaking)
    {
        $stocktakingId = $stocktaking->getId();

        $currentItemsSelect = $this->stocktakingItemRepository->getListByStocktakingId($stocktakingId)->getSelect();
        return $this->stocktakingResourceModel->getStocktakingItemsToMoveToArchiveSelect(
            $stocktaking,
            $currentItemsSelect
        );
    }

    /**
     * @inheritDoc
     */
    public function getStocktakingItemsToCancelSelect(StocktakingInterface $stocktaking)
    {
        $stocktakingId = $stocktaking->getId();

        $currentItemsSelect = $this->stocktakingItemRepository->getListByStocktakingId($stocktakingId)->getSelect();
        return $this->stocktakingResourceModel->getStocktakingItemsToMoveToArchiveSelect(
            $stocktaking,
            $currentItemsSelect
        );
    }

    /**
     * @inheritDoc
     */
    public function createAdjustStock(StocktakingInterface $stocktaking)
    {
        $stocktakingItems = $this->stocktakingItemRepository->getListByStocktakingId($stocktaking->getId());

        $eventData = new \Magento\Framework\DataObject(
            [
                'stocktaking' => $stocktaking,
                'stocktaking_items' => $stocktakingItems,
                'is_created_adjust_stock' => false
            ]
        );
        $this->eventManager->dispatch(
            'stocktaking_create_adjust_stock',
            ['event_data' => $eventData]
        );

        $isCreatedAdjustStock = $eventData->getIsCreatedAdjustStock();

        if (!$isCreatedAdjustStock) {
            $this->processChangeQuantity($stocktaking, $stocktakingItems);
            $isCreatedAdjustStock = true;
        }
        return $isCreatedAdjustStock;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function processChangeQuantity(
        StocktakingInterface $stocktaking,
        StocktakingItemCollection $stocktakingItems
    ) {
        if (!$stocktaking->getId() || !$stocktaking->getSourceCode()) {
            return;
        }
        $sourceItems = [];
        $packItems = 0;
        $i = 0;

        $movements = [];
        $newQtyData = [];

        if ($this->isEnableInventoryMovement()) {
            $getCurrentUser = $this->objectManager->get(GetCurrentUserInterface::class);
            /** @var DateTime $dateTime */
            $dateTime = $this->objectManager->get(DateTime::class);
            /** @var CurrentUserInterface $currentUser */
            $currentUser = $getCurrentUser->getCurrentUser();
        }

        foreach ($stocktakingItems as $item) {
            $sourceItem = [
                Sources::COL_SOURCE_CODE => $stocktaking->getSourceCode(),
                Sources::COL_SKU => $item->getProductSku(),
                Sources::COL_QTY => $item->getCountedQty(),
                Sources::COL_STATUS => $item->getCountedQty() > 0
                    ? SourceItemInterface::STATUS_IN_STOCK
                    : SourceItemInterface::STATUS_OUT_OF_STOCK
            ];
            $sourceItems[$packItems][] = $sourceItem;
            $i++;
            if ($i == 500) {
                $i = 0;
                $packItems++;
            }

            if ($this->isEnableInventoryMovement()) {
                // Prepare data for inventory movement log
                $movement = [
                    InventoryMovementInterface::PRODUCT_SKU => $item->getProductSku(),
                    InventoryMovementInterface::SOURCE_CODE => $stocktaking->getSourceCode(),
                    InventoryMovementInterface::OLD_QTY => $item->getQtyInSource(),
                    InventoryMovementInterface::CHANGE_QTY => $item->getCountedQty() - $item->getQtyInSource(),
                    InventoryMovementInterface::IS_IN_STOCK => $sourceItem[Sources::COL_STATUS],
                    InventoryMovementInterface::CREATED_AT => $dateTime->gmtDate(),
                    InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_STOCK_TAKING,
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL => __("New Stock Taking No"),
                        InventoryMovementInterface::OBJECT_TYPE => self::OBJECT_TYPE_STOCK_TAKING,
                        InventoryMovementInterface::OBJECT_ID => $stocktaking->getId(),
                        InventoryMovementInterface::OBJECT_INCREMENT_ID => $stocktaking->getCode()
                    ]
                ];
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $dateTime->timestamp();
                $movement[InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_FAIL;

                if ($currentUser) {
                    $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                    $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                    $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                }
                $movements[$item->getProductSku()] = $movement;
                $newQtyData[$item->getProductSku()] = $item->getCountedQty();
            }
        }

        if ($this->isEnableInventoryMovement()) {
            /* Save log before processing */
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

            if (count($movements)) {
                $this->eventManager->dispatch('inventory_movement_log_skip_default');
            }
        }

        $this->saveSourceItem($sourceItems);

        if ($this->isEnableInventoryMovement()) {
            /* Save log after processing */
            foreach (array_keys($movements) as $key) {
                $movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
                $movements[$key][InventoryMovementInterface::NEW_QTY] = $newQtyData[$key];
            }
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);
        }
    }

    /**
     * SaveSourceItem
     *
     * @param array $sourceItems
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function saveSourceItem($sourceItems)
    {
        if (!empty($sourceItems)) {
            foreach ($sourceItems as $items) {
                $items = $this->sourceItemConvert->convert($items);
                $this->sourceItemsSave->execute($items);
            }
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
