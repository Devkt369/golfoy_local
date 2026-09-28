<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\AdjustStock\Model\AdjustStock;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\AdjustStock\Api\AdjustStock\AdjustStockManagementInterface;
use Magestore\AdjustStock\Api\Data\AdjustStock\AdjustStockInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryImportExport\Model\Import\SourceItemConvert;
use Magento\InventoryImportExport\Model\Import\Sources;
use Magestore\AdjustStock\Api\Data\AdjustStock\ProductInterface;
use Magestore\AdjustStock\Api\IncrementIdManagementInterface;
use Magestore\AdjustStock\Api\MultiSourceInventory\StockManagementInterface;
use Magestore\AdjustStock\Model\AdjustStockFactory;
use Magestore\AdjustStock\Model\ResourceModel\AdjustStock\Collection;
use Magestore\AdjustStock\Model\ResourceModel\AdjustStock\GlobalStock\Collection as GlobalStockCollection;
use Magestore\AdjustStock\Model\ResourceModel\AdjustStock\Product\Collection as AdjustProductCollection;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerialize;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class AdjustStockManagement
 *
 * Use for managing adjust stock
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AdjustStockManagement implements AdjustStockManagementInterface
{
    /**
     * Stock movement action type for stock adjustment
     */
    const ACTION_TYPE_MAGESTORE_ADJUST = 'magestore_adjust';

    /**
     * Stock movement object type for stock adjustment
     */
    const OBJECT_TYPE_MAGESTORE_ADJUST = 'magestore_adjust';

    /**
     * Adjust stock batch size xml path config
     */
    const ADJUST_STOCK_BATCH_SIZE_XML_PATH = 'adjuststock/general/batch_adjust_size';

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     *
     * @var Session
     */
    protected $authSession;

    /**
     * @var IncrementIdManagementInterface
     */
    protected $_incrementIdManagement;

    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var AdjustStockFactory
     */
    protected $adjustStockFactory;
    /**
     * @var ProductFactory
     */
    protected $adjustStockProductFactory;
    /**
     * @var \Magestore\AdjustStock\Model\ResourceModel\AdjustStock\Product
     */
    protected $adjustStockProductResource;
    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    protected $getSourceItemBySourceCodeAndSku;
    /**
     * @var SourceItemsSaveInterface
     */
    protected $sourceItemsSave;
    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;
    /**
     * @var array|null
     */
    protected $data;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var SourceItemConvert
     */
    private $sourceItemConvert;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * AdjustStockManagement constructor.
     * @param IncrementIdManagementInterface $incrementIdManagement
     * @param DateTime $dateTime
     * @param Session $authSession
     * @param SourceRepositoryInterface $sourceRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param AdjustStockFactory $adjustStockFactory
     * @param ProductFactory $adjustStockProductFactory
     * @param \Magestore\AdjustStock\Model\ResourceModel\AdjustStock\Product $adjustStockProductResource
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param StockManagementInterface $stockManagement
     * @param ObjectManagerInterface $objectManager
     * @param SourceItemConvert $sourceItemConvert
     * @param EventManager $eventManager
     * @param array|null $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        IncrementIdManagementInterface $_incrementIdManagement,
        DateTime $dateTime,
        Session $authSession,
        SourceRepositoryInterface $sourceRepository,
        ScopeConfigInterface $scopeConfig,
        AdjustStockFactory $adjustStockFactory,
        ProductFactory $adjustStockProductFactory,
        \Magestore\AdjustStock\Model\ResourceModel\AdjustStock\Product $adjustStockProductResource,
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        SourceItemsSaveInterface $sourceItemsSave,
        StockManagementInterface $stockManagement,
        ObjectManagerInterface $_objectManager,
        SourceItemConvert $sourceItemConvert,
        EventManager $eventManager,
        ?array $data = null
    ) {
        $this->_incrementIdManagement = $_incrementIdManagement;
        $this->dateTime = $dateTime;
        $this->authSession = $authSession;
        $this->sourceRepository = $sourceRepository;
        $this->scopeConfig = $scopeConfig;
        $this->adjustStockFactory = $adjustStockFactory;
        $this->adjustStockProductFactory = $adjustStockProductFactory;
        $this->adjustStockProductResource = $adjustStockProductResource;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->stockManagement = $stockManagement;
        $this->_objectManager = $_objectManager;
        $this->sourceItemConvert = $sourceItemConvert;
        $this->data = $data;
        $this->eventManager = $eventManager;
    }

    /**
     * Create adjustment
     *
     * @param AdjustStockInterface $adjustStock
     * @param array $data
     * @return AdjustStockInterface
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function createAdjustment(AdjustStockInterface $adjustStock, $data)
    {
        $this->data = $data;
        $adjustStockCode = isset($data[AdjustStockInterface::ADJUSTSTOCK_CODE]) ?
            $data[AdjustStockInterface::ADJUSTSTOCK_CODE] :
            $this->generateCode();

        if (!$adjustStock->getId()) {
            $createdAt = isset($data[AdjustStockInterface::CREATED_AT]) ?
                $data[AdjustStockInterface::CREATED_AT] :
                $this->dateTime->gmtDate();
            $curUser = $this->authSession->getUser();
            $createdBy = isset($data[AdjustStockInterface::CREATED_BY]) ?
                $data[AdjustStockInterface::CREATED_BY] :
                ($curUser ? $curUser->getUserName() : null);

            /* load warehouse data if $data[AdjustStockInterface::WAREHOUSE_NAME] is null */
            if (!isset($data[AdjustStockInterface::SOURCE_NAME])) {
                $warehouse = $this->sourceRepository->get($data[AdjustStockInterface::SOURCE_CODE]);
                $data[AdjustStockInterface::SOURCE_NAME] = $warehouse->getName();
                $data[AdjustStockInterface::SOURCE_CODE] = $warehouse->getSourceCode();
            }

            /* prepare data for stock adjustment */
            $adjustStock->setReason($data[AdjustStockInterface::REASON])
                ->setStatus(AdjustStockInterface::STATUS_PENDING)
                ->setSourceCode($data[AdjustStockInterface::SOURCE_CODE])
                ->setSourceName($data[AdjustStockInterface::SOURCE_NAME])
                ->setCreatedAt($createdAt)
                ->setCreatedBy($createdBy)
                ->setAdjuststockCode($adjustStockCode);
        } else {
            $adjustStock->setReason($data[AdjustStockInterface::REASON])
                ->setAdjuststockCode($adjustStockCode);
        }

        $this->createSelection($adjustStock, $data);

        return $adjustStock;
    }

    /**
     * Modify adjust products data
     *
     * @param AdjustStockInterface $adjustStock
     * @param array $data
     * @throws AlreadyExistsException
     */
    public function createSelection($adjustStock, $data)
    {
        $products = $data['products'];
        $adjustStock->getResource()->save($adjustStock);

        // clear stock adjustment products before update product data
        /** @var AdjustProductCollection $adjustStockProductCollection */
        $adjustStockProductCollection = $this->adjustStockProductFactory->create()->getCollection();
        $adjustStockProductCollection->addFieldToFilter('adjuststock_id', $adjustStock->getId());
        foreach ($adjustStockProductCollection as $item) {
            $this->adjustStockProductResource->delete($item);
        }

        foreach ($products as $product) {
            /** @var ProductInterface $adjustStockProduct */
            $adjustStockProduct = $this->adjustStockProductFactory->create();
            $adjustStockProduct->setData($product);
            $adjustStockProduct->setAdjuststockId($adjustStock->getId());

            $this->adjustStockProductResource->save($adjustStockProduct);
        }
    }

    /**
     * Generate unique code of Stock Adjustment
     *
     * @return string
     */
    public function generateCode()
    {
        return $this->_incrementIdManagement->getNextCode(AdjustStockInterface::PREFIX_CODE);
    }

    /**
     * @inheritdoc
     */
    public function checkAdjustmentCode($adjustmentId, $adjustmentCode)
    {
        /** @var Collection $collection */
        $collection = $this->adjustStockFactory->create()->getCollection();
        $collection->addFieldToFilter('adjuststock_code', $adjustmentCode);
        if ($adjustmentId) {
            $collection->addFieldToFilter('adjuststock_id', ['neq' => $adjustmentId]);
        }

        if ($collection->getSize()) {
            return false;
        }
        return true;
    }

    /**
     * Complete a stock adjustment
     *
     * @param AdjustStockInterface $adjustStock
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function complete(AdjustStockInterface $adjustStock)
    {
        $moduleManager = $this->_objectManager->get(ModuleManager::class);
        $isEnableInventoryMovement = $moduleManager->isEnabled('Magestore_InventoryMovementApi');

        if ($isEnableInventoryMovement) {
            $movements = [];
            $getCurrentUser = $this->_objectManager->get(GetCurrentUserInterface::class);
            $currentUser = $getCurrentUser->getCurrentUser();
        }

        // Correct current qty in source before complete adjustment
        $this->adjustStockProductResource->correctCurrectQty($adjustStock);

        /** @var AdjustProductCollection $adjustStockProductCollection */
        $adjustStockProductCollection = $this->adjustStockProductFactory->create()->getCollection();
        $adjustStockProductCollection->addFieldToFilter('adjuststock_id', $adjustStock->getId());
        $productData = [];
        if ($adjustStockProductCollection->getSize()) {
            foreach ($adjustStockProductCollection as $product) {
                $productData[$product->getProductSku()] = $product->getNewQty();

                if ((float) $product->getChangeQty() && $isEnableInventoryMovement) {
                    // Prepare data for stock movements
                    $movement = [
                        InventoryMovementInterface::PRODUCT_SKU => $product->getProductSku(),
                        InventoryMovementInterface::SOURCE_CODE => $adjustStock->getSourceCode(),
                        InventoryMovementInterface::OLD_QTY => $product->getOldQty(),
                        InventoryMovementInterface::CHANGE_QTY => $product->getChangeQty(),
                        InventoryMovementInterface::IS_IN_STOCK => 1,
                        InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                        InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_MAGESTORE_ADJUST,
                        InventoryMovementInterface::METADATA => [
                            InventoryMovementInterface::ACTION_LABEL => __("New Adjust Stock No"),
                            InventoryMovementInterface::OBJECT_TYPE => self::OBJECT_TYPE_MAGESTORE_ADJUST,
                            InventoryMovementInterface::OBJECT_ID => $adjustStock->getId(),
                            InventoryMovementInterface::OBJECT_INCREMENT_ID => $adjustStock->getAdjustStockCode()
                        ]
                    ];
                    if ($currentUser) {
                        $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                        $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                        $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                    }
                    $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                        $movement[InventoryMovementInterface::PRODUCT_SKU]
                        . $movement[InventoryMovementInterface::SOURCE_CODE]
                        . $movement[InventoryMovementInterface::CHANGE_QTY]
                        . $this->dateTime->timestamp();
                    $movement[InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_FAIL;

                    $movements[$product->getProductSku()] = $movement;
                }
            }
        }

        if ($isEnableInventoryMovement) {
            /* Save movements log before processing */
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

            if (count($movements)) {
                $this->eventManager->dispatch('inventory_movement_log_skip_default');
            }
        }
        // apply stock adjustment
        $this->applyStockAdjustment($adjustStock, $productData);

        if ($isEnableInventoryMovement) {
            /* Save movements log before processing */
            foreach (array_keys($movements) as $key) {
                $movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
                $movements[$key][InventoryMovementInterface::NEW_QTY] = $productData[$key];
            }
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);
        }

        /* mark as completed */
        $confirmedAt = $this->data[AdjustStockInterface::CONFIRMED_AT] ?? $this->dateTime->gmtDate();
        $curUser = $this->authSession->getUser();
        $confirmedBy = $this->data[AdjustStockInterface::CONFIRMED_BY] ?? ($curUser ? $curUser->getUserName() : null);

        $adjustStock->setStatus(AdjustStockInterface::STATUS_COMPLETED)
                ->setConfirmedBy($confirmedBy)
                ->setConfirmedAt($confirmedAt);
        $adjustStock->getResource()->save($adjustStock);
    }

    /**
     * Change quantity in source
     *
     * @param AdjustStockInterface $adjustStock
     * @param array $productData
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws ValidationException
     */
    public function applyStockAdjustment($adjustStock, $productData)
    {
        if (!$adjustStock->getId() || !$adjustStock->getSourceCode()) {
            return;
        }

        $batchSize = $this->scopeConfig->getValue(self::ADJUST_STOCK_BATCH_SIZE_XML_PATH);

        $sourceItems = [];
        $packItems = 0;
        $i = 0;

        foreach ($productData as $sku => $newQty) {
            $sourceItem = [
                Sources::COL_SOURCE_CODE => $adjustStock->getSourceCode(),
                Sources::COL_SKU => (string)$sku,
                Sources::COL_QTY => $newQty,
                Sources::COL_STATUS => $newQty > 0
                    ? SourceItemInterface::STATUS_IN_STOCK
                    : SourceItemInterface::STATUS_OUT_OF_STOCK
            ];
            $sourceItems[$packItems][] = $sourceItem;
            $i++;
            if ($i == $batchSize) {
                $i = 0;
                $packItems++;
            }
        }

        if (!empty($sourceItems)) {
            foreach ($sourceItems as $items) {
                $items = $this->sourceItemConvert->convert($items);
                $this->sourceItemsSave->execute($items);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function isShowThumbnail()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getSelectBarcodeProductListJson($adjustStockId = null)
    {
        $collection = $this->getProductCollection();
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
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function getProductJsonByBarcode(string $barcode)
    {
        $collection = $this->getProductCollection();
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
     * Convert product barcode result to json string
     *
     * @param array $items
     * @return string
     * @throws NoSuchEntityException
     */
    public function convertProductBarcodeResultToJson(array $items)
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
        foreach ($result as &$item) {
            if (isset($item['image'])) {
                $item['image_url'] = $path . 'catalog/product' . $item['image'];
            }
        }

        return $this->_objectManager
            ->get(JsonSerialize::class)
            ->serialize($result);
    }

    /**
     * @inheritdoc
     */
    public function getProductCollection()
    {
        $collection = $this->_objectManager->create(GlobalStockCollection::class);

        return $collection;
    }
}
