<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Model\Inventory\Model\SourceItem\Command;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ObjectManager;
use Magestore\Webpos\Model\ResourceModel\InventoryCatalog\DecrementQtyForLegacyStock;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magestore\Webpos\Model\ResourceModel\InventoryCatalog\SetDataToLegacyStockStatus
    as WebposSetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Synchronization between legacy Stock Items and saved Source Items after decrement quantity of stock item
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SynchronizeLegacyStockAfterDecrementStock
{
    /**
     * @var DecrementQtyForLegacyStock
     */
    private $decrementQuantityForLegacyCatalogInventory;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var Processor
     */
    private $indexerProcessor;

    /**
     * @var SetDataToLegacyStockStatus|WebposSetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $legacyStockItemCriteriaFactory;

    /**
     * @var StockItemRepositoryInterface
     */
    private $legacyStockItemRepository;

    /**
     * @var StockStateProviderInterface
     */
    private $stockStateProvider;

    /**
     * SynchronizeLegacyStockAfterDecrementStock constructor.
     * @param DecrementQtyForLegacyStock $decrementQuantityForLegacyCatalogInventory
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param Processor $indexerProcessor
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param StockStateProviderInterface $stockStateProvider
     */
    public function __construct(
        DecrementQtyForLegacyStock $decrementQuantityForLegacyCatalogInventory,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        Processor $indexerProcessor,
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        StockStateProviderInterface $stockStateProvider
    ) {
        $this->decrementQuantityForLegacyCatalogInventory = $decrementQuantityForLegacyCatalogInventory;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->indexerProcessor = $indexerProcessor;
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->stockStateProvider = $stockStateProvider;
        $this->setDataToLegacyStockStatus = class_exists(SetDataToLegacyStockStatus::class) ?
            ObjectManager::getInstance()->get(SetDataToLegacyStockStatus::class)
            : ObjectManager::getInstance()->get(WebposSetDataToLegacyStockStatus::class);
    }

    /**
     * Execute the function sync
     *
     * @param array $sourceItemDecrementData
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(array $sourceItemDecrementData): void
    {
        $productIds = [];
        $this->decrementQuantityForLegacyCatalogInventory->execute($sourceItemDecrementData);
        $sourceItems = array_column($sourceItemDecrementData, 'source_item');
        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();
            $productId = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
            $productIds[] = $productId;

            $legacyStockItem = $this->getLegacyStockItem($productId);
            if (null === $legacyStockItem) {
                continue;
            }
            $isInStock = (int)$sourceItem->getStatus();
            if ($legacyStockItem->getManageStock()) {
                $legacyStockItem->setIsInStock($isInStock);
                $legacyStockItem->setQty((float)$sourceItem->getQuantity());

                if (false === $this->stockStateProvider->verifyStock($legacyStockItem)) {
                    $isInStock = 0;
                }
            }
            $this->setDataToLegacyStockStatus->execute(
                (string)$sourceItem->getSku(),
                (float)$sourceItem->getQuantity(),
                $isInStock
            );
        }
        if ($productIds) {
            $this->indexerProcessor->reindexList($productIds);
        }
    }

    /**
     * Returns StockItem from legacy inventory.
     *
     * @param int $productId
     * @return null|StockItemInterface
     */
    private function getLegacyStockItem(int $productId): ?StockItemInterface
    {
        $searchCriteria = $this->legacyStockItemCriteriaFactory->create();

        $searchCriteria->addFilter(StockItemInterface::PRODUCT_ID, StockItemInterface::PRODUCT_ID, $productId);
        $searchCriteria->addFilter(StockItemInterface::STOCK_ID, StockItemInterface::STOCK_ID, Stock::DEFAULT_STOCK_ID);

        $stockItemCollection = $this->legacyStockItemRepository->getList($searchCriteria);
        if ($stockItemCollection->getTotalCount() === 0) {
            return null;
        }

        $stockItems = $stockItemCollection->getItems();
        $stockItem = reset($stockItems);
        return $stockItem;
    }
}
