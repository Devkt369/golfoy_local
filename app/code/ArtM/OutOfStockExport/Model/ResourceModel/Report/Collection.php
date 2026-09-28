<?php
namespace ArtM\OutOfStockExport\Model\ResourceModel\Report;

class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * Apply the out-of-stock report logic:
     *
     * SIMPLE: visibility = Search (3) or Catalog/Search (4) AND out of stock
     *
     * CONFIGURABLE: visibility = Search or Catalog/Search
     *               AND all associated simple children are out of stock
     */
    public function applyReportFilters()
    {
        $this->addAttributeToSelect(['name', 'visibility']);

        $connection    = $this->getConnection();
        $entityTable   = $this->getTable('catalog_product_entity');
        $intTable      = $this->getTable('catalog_product_entity_int');
        $stockTable    = $this->getTable('cataloginventory_stock_item');
        $relationTable = $this->getTable('catalog_product_relation');

        $visibilities = [3, 4]; // 3 = Search, 4 = Catalog/Search

        // visibility EAV attribute id (column moved off catalog_product_entity in 2.3+)
        $visibilityAttrId = (int)$this->getAttribute('visibility')->getAttributeId();

        // --- Simple products: visible + out of stock ---
        $simpleSelect = $connection->select()
            ->from(['e' => $entityTable], ['entity_id'])
            ->join(
                ['v' => $intTable],
                "v.entity_id = e.entity_id AND v.attribute_id = {$visibilityAttrId} AND v.store_id = 0",
                []
            )
            ->join(['si' => $stockTable], 'si.product_id = e.entity_id', [])
            ->where('e.type_id = ?', 'simple')
            ->where('v.value IN (?)', $visibilities)
            ->where('si.is_in_stock = ?', 0);

        // --- Configurable parents where NO child is in stock ---
        $childInStockSql = sprintf(
            'SELECT 1 FROM %s rel INNER JOIN %s si ON si.product_id = rel.child_id
             WHERE rel.parent_id = e.entity_id AND si.is_in_stock = 1',
            $relationTable,
            $stockTable
        );

        $configurableSelect = $connection->select()
            ->from(['e' => $entityTable], ['entity_id'])
            ->join(
                ['v' => $intTable],
                "v.entity_id = e.entity_id AND v.attribute_id = {$visibilityAttrId} AND v.store_id = 0",
                []
            )
            ->where('e.type_id = ?', 'configurable')
            ->where('v.value IN (?)', $visibilities)
            ->where('NOT EXISTS (' . $childInStockSql . ')');

        $unionSelect = $connection->select()->union([$simpleSelect, $configurableSelect]);

        $this->addFieldToFilter('entity_id', ['in' => $unionSelect]);

        // Join stock data for grid columns (qty / stock status)
        $this->joinTable(
            [$stockTable, 'si'],
            'product_id=entity_id',
            ['qty' => 'qty', 'stock_status' => 'is_in_stock'],
            null,
            'left'
        );

        return $this;
    }
}