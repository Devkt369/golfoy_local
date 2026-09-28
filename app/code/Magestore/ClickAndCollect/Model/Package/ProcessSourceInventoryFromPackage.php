<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Package;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\InventoryImportExport\Model\Import\Sources;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magestore\Core\Api\Data\CurrentUserInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\ProcessSourceInventoryFromPackageInterface;
use Magestore\ClickAndCollectApi\Api\PackageItemRepositoryInterface;
use Magestore\Core\Api\SaveSourceItemsInterface;
use Magestore\ClickAndCollectApi\Api\Package\GetCurrentLocationIdInterface;
use Magestore\Core\Api\GetSourcesCodeByStockIdInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magestore\Core\Api\SourceManagementInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

/**
 * Class Validate Package
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessSourceInventoryFromPackage implements ProcessSourceInventoryFromPackageInterface
{
    /**
     * Stock movement action type for clickandcollect
     */
    const ACTION_TYPE_CLICK_AND_COLLECT = 'clickandcollect';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SaveSourceItemsInterface
     */
    private $saveSourceItems;

    /**
     * @var PackageItemRepositoryInterface
     */
    private $packageItemRepository;

    /**
     * @var GetCurrentLocationIdInterface
     */
    private $getCurrentLocationId;

    /**
     * @var GetSourcesCodeByStockIdInterface
     */
    private $getSourcesCodeByStockId;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var GetCurrentUserInterface
     */
    private $getCurrentUser;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var SourceManagementInterface
     */
    private $sourceManagement;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @var array
     */
    private $movements = [];

    /**
     * ProcessSourceInventoryFromPackage constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SaveSourceItemsInterface $saveSourceItems
     * @param PackageItemRepositoryInterface $packageItemRepository
     * @param GetCurrentLocationIdInterface $getCurrentLocationId
     * @param GetSourcesCodeByStockIdInterface $getSourcesCodeByStockId
     * @param LocationRepositoryInterface $locationRepository
     * @param GetCurrentUserInterface $getCurrentUser
     * @param DateTime $dateTime
     * @param SourceManagementInterface $sourceManagement
     * @param EventManagerInterface $eventManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SaveSourceItemsInterface $saveSourceItems,
        PackageItemRepositoryInterface $packageItemRepository,
        GetCurrentLocationIdInterface $getCurrentLocationId,
        GetSourcesCodeByStockIdInterface $getSourcesCodeByStockId,
        LocationRepositoryInterface $locationRepository,
        GetCurrentUserInterface $getCurrentUser,
        DateTime $dateTime,
        SourceManagementInterface $sourceManagement,
        EventManagerInterface $eventManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->saveSourceItems = $saveSourceItems;
        $this->packageItemRepository = $packageItemRepository;
        $this->getCurrentLocationId = $getCurrentLocationId;
        $this->getSourcesCodeByStockId = $getSourcesCodeByStockId;
        $this->locationRepository = $locationRepository;
        $this->getCurrentUser = $getCurrentUser;
        $this->dateTime = $dateTime;
        $this->sourceManagement = $sourceManagement;
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritDoc
     */
    public function reserveSourceItemQuantityFromPackage(PackageInterface $package): void
    {
        $this->processChangeSourceItemQuantityFromPackage($package);
    }

    /**
     * @inheritDoc
     */
    public function returnSourceItemQuantityFromPackage(PackageInterface $package): void
    {
        $this->processChangeSourceItemQuantityFromPackage($package, false);
    }

    /**
     * Process Change Source Item Quantity From Package
     *
     * @param PackageInterface $package
     * @param bool $isSubtract
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processChangeSourceItemQuantityFromPackage(PackageInterface $package, $isSubtract = true)
    {
        if (!$this->getCurrentLocationId->getCurrentLocationId()) {
            throw new LocalizedException(__('Can not find location.'));
        }
        try {
            $location = $this->locationRepository->getById($this->getCurrentLocationId->getCurrentLocationId());
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Can not find location.'));
        }
        $sourcesCode = $this->getSourcesCodeByStockId->execute($location->getStockId());
        if (!count($sourcesCode)) {
            throw new LocalizedException(__('Can not find corresponding source.'));
        }
        $currentSourceCode = current($sourcesCode);
        $itemsQty = $this->getItemsQuantity($package, $currentSourceCode, $isSubtract);
        $sourceItems = [];
        foreach ($itemsQty as $sku => $qty) {
            $sourceItem = [
                Sources::COL_SOURCE_CODE => $currentSourceCode,
                Sources::COL_SKU => $sku,
                Sources::COL_QTY => $qty,
                Sources::COL_STATUS => (int)($qty > 0)
            ];
            $sourceItems[] = $sourceItem;
        }
        try {
            $this->processSaveSourceItems($sourceItems);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Can save source items.'));
        }
    }

    /**
     * Get Items Quantity
     *
     * @param PackageInterface $package
     * @param string $sourceCode
     * @param bool $isSubtract
     * @return array
     */
    public function getItemsQuantity(PackageInterface $package, string $sourceCode, bool $isSubtract)
    {
        $itemsQty = [];
        $packageItems = $this->packageItemRepository->getItemsByPackageId($package->getPackageId());
        foreach ($packageItems as $item) {
            $itemsQty[$item->getProductSku()] = (float)$item->getPackedQty();
        }
        $sourceItems = $this->sourceManagement->getListProductInSource(
            $sourceCode,
            array_keys($itemsQty)
        );
        foreach ($itemsQty as $sku => $qty) {
            $oldQty = $sourceItems[$sku]->getQuantity();
            // Prepare for inventory movement
            if ($this->isEnableInventoryMovement()) {
                $order = $this->orderRepository->get($package->getOrderId());
                $movement = [
                    InventoryMovementInterface::OLD_QTY => $oldQty,
                    InventoryMovementInterface::SOURCE_CODE => $sourceCode,
                    InventoryMovementInterface::CHANGE_QTY => $isSubtract ? -$qty : $qty,
                    InventoryMovementInterface::PRODUCT_SKU => $sku,
                    InventoryMovementInterface::ACTION_TYPE => self::ACTION_TYPE_CLICK_AND_COLLECT,
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL => $isSubtract
                            ? __("Create Click & Collect Package of Order No")
                            : __("Cancel Click & Collect Package of Order No"),
                        InventoryMovementInterface::OBJECT_TYPE => 'order',
                        InventoryMovementInterface::OBJECT_ID => $order->getEntityId(),
                        InventoryMovementInterface::OBJECT_INCREMENT_ID => $order->getIncrementId()
                    ]

                ];
                $this->movements[$sku] = $movement;
            }

            $itemsQty[$sku] = $oldQty + ($isSubtract ? -$qty : $qty);
        }

        return $itemsQty;
    }

    /**
     * Process Save Source Items
     *
     * @param array $sourceItems
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function processSaveSourceItems(array $sourceItems)
    {
        $newQtyData = [];
        if ($this->isEnableInventoryMovement()) {
            // Prepare data for inventory movement log
            foreach ($sourceItems as $sourceItem) {
                $movement = $this->movements[$sourceItem[Sources::COL_SKU]];
                $movement[InventoryMovementInterface::IS_IN_STOCK] = $sourceItem[Sources::COL_STATUS];
                $movement[InventoryMovementInterface::CREATED_AT] = $this->dateTime->gmtDate();
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();
                $movement[InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_FAIL;

                /** @var CurrentUserInterface $currentUser */
                $currentUser = $this->getCurrentUser->getCurrentUser();
                if ($currentUser) {
                    $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                    $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                    $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
                }
                $this->movements[$sourceItem[Sources::COL_SKU]] = $movement;
                $newQtyData[$sourceItem[Sources::COL_SKU]] = $sourceItem[Sources::COL_QTY];
            }
            /* Save log before processing */
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $this->movements]);
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }

        $this->saveSourceItems->execute($sourceItems);

        if ($this->isEnableInventoryMovement()) {
            /* Save log after processing */
            foreach (array_keys($this->movements) as $key) {
                $this->movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
                $this->movements[$key][InventoryMovementInterface::NEW_QTY] = $newQtyData[$key];
            }
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $this->movements]);
        }
    }

    /**
     * Is Enable Inventory Movement
     *
     * @return bool
     */
    public function isEnableInventoryMovement()
    {
        $moduleManager = ObjectManager::getInstance()->get(ModuleManager::class);
        return $moduleManager->isEnabled('Magestore_InventoryMovementApi');
    }
}
