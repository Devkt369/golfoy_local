<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPointy\Model\ResourceModel\Inventory;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface;
use Magestore\WebposPointy\Log\Logger;

/**
 * Resource Inventory
 */
class Inventory
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Inventory constructor.
     * @param ResourceConnection $resourceConnection
     * @param StockManagementInterface $stockManagement
     * @param Logger $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StockManagementInterface $stockManagement,
        Logger $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->stockManagement = $stockManagement;
        $this->logger = $logger;
    }

    /**
     * Get Products
     *
     * @param LocationInterface $location
     * @return array
     */
    public function getProducts(LocationInterface $location)
    {
        $connection = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');

        $select = $connection->select();
        $select->from(['catalog_product_entity' => $productTable]);
        $this->filterProductByLocation($select, $location);
        $this->filterProductByLastSummitTime($select, $location);

        return $connection->fetchAll($select);
    }

    /**
     * Filter Product By Location
     *
     * @param Select $select
     * @param LocationInterface $location
     */
    public function filterProductByLocation(Select $select, LocationInterface $location)
    {
        $sourceItemTable = $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM);
        $linkedSources = $this->stockManagement->getLinkedSourceCodesByStockId($location->getStockId());
        $select->joinLeft(
            ['inventory_source_item' => $sourceItemTable],
            "catalog_product_entity.sku = inventory_source_item.sku
                AND inventory_source_item.source_code IN ('" . implode("', '", $linkedSources) . "')",
            ['source_code']
        )->group('catalog_product_entity.entity_id')
        ->having('inventory_source_item.source_code IN (?)', $linkedSources);
    }

    /**
     * Filter Product By Last Summit Time
     *
     * @param Select $select
     * @param LocationInterface $location
     */
    public function filterProductByLastSummitTime(Select $select, LocationInterface $location)
    {
        $lastSubmitTime = $location->getPointyLastSubmitInventory();
        if ($lastSubmitTime) {
            $select->where("catalog_product_entity.updated_at >= ?", $lastSubmitTime);
        }
    }

    /**
     * Get Deleted Products
     *
     * @param LocationInterface $location
     * @return array
     */
    public function getDeletedProducts(LocationInterface $location)
    {
        $lastSubmitTime = $location->getPointyLastSubmitInventory();
        if (!$lastSubmitTime) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $productDeletedTable = $this->resourceConnection->getTableName('webpos_product_deleted');

        $select = $connection->select();
        $select->from(['catalog_product_entity' => $productTable]);
        $select->joinInner(
            ['webpos_product_deleted' => $productDeletedTable],
            "catalog_product_entity.entity_id = webpos_product_deleted.product_id
                AND webpos_product_deleted.stock_id = " . $location->getStockId() . "
                AND webpos_product_deleted.deleted_at >= '" . $lastSubmitTime . "'",
            []
        );

        return $connection->fetchAll($select);
    }
}
