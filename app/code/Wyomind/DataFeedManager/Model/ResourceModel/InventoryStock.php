<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Model\ResourceModel;

/**
 * Class InventoryStock
 * @package Wyomind\DataFeedManager\Model\ResourceModel
 */
class InventoryStock extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public $stocks;
    /**
     * @var null|string
     */
    private $inventoryReservationTable = null;
    /**
     * @var string
     */
    private $catalogProductEntityTable;
    public $objectManager;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        /** @delegation off */
        $connectionName = null
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context, $connectionName);
        $stockSourceLinkCollection = $this->objectManager->create("\\Magento\\Inventory\\Model\\ResourceModel\\StockSourceLink\\Collection");
        foreach ($stockSourceLinkCollection as $stock) {
            $this->stocks[$stock->getStockId()][] = $stock->getSourceCode();
        }
        $this->inventoryReservationTable = $this->getTable("inventory_reservation");
        $this->catalogProductEntityTable = $this->getTable("catalog_product_entity");
    }
    /**
     * Resource initialization
     * @return void
     */
    public function _construct()
    {
        $this->_init('datafeedmanager_feeds', 'id');
    }
    /**
     * Collect all data related to the stock inventory other that the default stock
     * @param $stockList
     * @return array
     */
    public function collectStocks($stockList)
    {
        $stocks = [];
        foreach ($this->stocks as $stockId => $stock) {
            if (in_array($stockId, $stockList)) {
                $inventoryStockTable = $this->getTable("inventory_stock_" . $stockId);
                $select = $this->getConnection()->select();
                //                $select->from(["inventory_stock" => $inventoryStockTable])->reset('columns')
                //                    ->columns(["cpe.entity_id", new \Zend_Db_Expr("inventory_stock.quantity+IF(ISNULL(SUM(ir.quantity)),0,SUM(ir.quantity)) AS quantity"), new \Zend_Db_Expr("MAX(is_salable) as is_salable")])
                //                    ->joinLeft(["ir" => $this->inventoryReservationTable], "ir.sku = inventory_stock.sku", [])
                //                    ->joinLeft(["cpe" => $this->catalogProductEntityTable], "inventory_stock.sku = cpe.sku", [])
                //                    ->group(["cpe.entity_id"]);
                $select->from(["inventory_stock" => $inventoryStockTable])->reset('columns')->columns(["cpe.entity_id", new \Zend_Db_Expr("inventory_stock.quantity + IF(ISNULL(ir_sum.quantity), 0, ir_sum.quantity)AS quantity"), new \Zend_Db_Expr("MAX(is_salable) as is_salable")])->joinLeft(["ir_sum" => new \Zend_Db_Expr("(SELECT sku, SUM(quantity) as quantity FROM `inventory_reservation` GROUP BY sku)")], "ir_sum.sku = inventory_stock.sku", [])->joinLeft(["cpe" => $this->catalogProductEntityTable], "inventory_stock.sku = cpe.sku", [])->group(["cpe.entity_id"]);
                $data = $this->getConnection()->fetchAll($select);
                foreach ($data as $product) {
                    $stocks[$stockId][$product["entity_id"]] = ["quantity" => $product["quantity"], "is_salable" => $product["is_salable"]];
                }
            }
        }
        return $stocks;
    }
    /**
     * @return array
     */
    public function collectSources($sourceList)
    {
        $sources = [];
        $inventorySourceItemTable = $this->getTable("inventory_source_item");
        $select = $this->getConnection()->select();
        $select->from(["isi" => $inventorySourceItemTable])->reset('columns')->columns(["cpe.entity_id", "isi.quantity", "isi.status", "isi.source_code"])->joinInner(["cpe" => $this->catalogProductEntityTable], "isi.sku = cpe.sku", [])->group(["cpe.entity_id", "isi.source_code"])->where("isi.source_code IN ('" . implode("','", array_unique($sourceList)) . "')");
        $data = $this->getConnection()->fetchAll($select);
        foreach ($data as $product) {
            $sources[$product["entity_id"]][$product["source_code"]] = ["quantity" => $product["quantity"], "is_salable" => $product["status"]];
        }
        return $sources;
    }
}