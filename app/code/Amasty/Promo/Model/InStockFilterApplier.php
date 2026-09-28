<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Model;

use Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;

class InStockFilterApplier
{
    private const MAGENTO_INVENTORYCATALOG_MODULE_NAME = 'Magento_InventoryCatalog';

    private const MSI_STOCK_TABLE = 'inventory_stock_%d';

    private const DEFAULT_STOCK_TABLE = 'cataloginventory_stock_status';

    private const STOCK_STATUS_ALIAS = 'stock_status';

    /**
     * @var bool
     */
    private $msiEnabled = null;

    /**
     * @var AbstractCollection
     */
    private $productsCollection;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var ?GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    public function __construct(
        Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    public function execute(AbstractCollection $productsCollection): AbstractCollection
    {
        $this->productsCollection = $productsCollection;

        if (!$this->joinMsiStockTable($productsCollection)) {
            $productsCollection->joinField(
                self::STOCK_STATUS_ALIAS,
                self::DEFAULT_STOCK_TABLE,
                StockStatus::KEY_STOCK_STATUS,
                'product_id = entity_id',
                '{{table}}.stock_id = 1',
                'left'
            )->addFieldToFilter(
                StockStatus::KEY_STOCK_STATUS,
                ['eq' => StockStatus::STATUS_IN_STOCK]
            );
        }

        return $productsCollection;
    }

    private function joinMsiStockTable(AbstractCollection $productsCollection): bool
    {
        $result = false;
        $StockIdGetter = $this->getStockIdInstance();
        if ($StockIdGetter instanceof GetStockIdForCurrentWebsite) {
            $stockId = $this->getStockIdForCurrentWebsite->execute();
            if ($this->isMsiStockTableExists($stockId)) {
                $productsCollection->getSelect()->joinLeft(
                    [self::STOCK_STATUS_ALIAS => $this->getMsiStockTable($stockId)],
                    sprintf(
                        'e.sku = %s.sku AND %s.is_salable = %s',
                        self::STOCK_STATUS_ALIAS,
                        self::STOCK_STATUS_ALIAS,
                        StockStatus::STATUS_IN_STOCK
                    ),
                    [StockStatus::KEY_STOCK_STATUS => 'is_salable']
                );

                $result = true;
            }
        }

        return $result;
    }

    private function getStockIdInstance(): ?GetStockIdForCurrentWebsite
    {
        if ($this->msiEnabled === null) {
            $this->msiEnabled = $this->moduleManager->isEnabled(self::MAGENTO_INVENTORYCATALOG_MODULE_NAME);
            if ($this->msiEnabled) {
                $this->getStockIdForCurrentWebsite = ObjectManager::getInstance()
                    ->get(GetStockIdForCurrentWebsite::class);
            }
        }

        return $this->getStockIdForCurrentWebsite;
    }

    private function isMsiStockTableExists(int $stockId): bool
    {
        return $this->productsCollection->getConnection()->isTableExists($this->getMsiStockTable($stockId));
    }

    private function getMsiStockTable(int $stockId): string
    {
        return $this->productsCollection->getTable(sprintf(self::MSI_STOCK_TABLE, $stockId));
    }

    public function _resetState(): void
    {
        $this->msiEnabled = null;
    }
}
