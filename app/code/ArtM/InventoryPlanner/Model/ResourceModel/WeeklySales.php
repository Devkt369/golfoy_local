<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class WeeklySales extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('artm_inventoryplanner_weekly_sales', 'id');
    }

    /**
     * Inserts new product/sku/week rows, or updates qty_sold on existing ones -
     * never duplicates a row for the same product/sku/week (see the unique key
     * in etc/db_schema.xml).
     *
     * $additive controls how an existing row's qty_sold is handled on conflict:
     * - false (default): replace it. Used by the manual "Sync Data" recompute,
     *   which is expected to be idempotent when the same range is run twice.
     * - true: add to it. Used by order-id-based "Sync Now", where each run only
     *   ever aggregates orders it hasn't seen before, so a still-open week's
     *   total must accumulate across runs rather than being overwritten.
     *
     * @param array<int, array{product_id:int, sku:string, week_start:string, week_end:string, qty_sold:float}> $rows
     */
    public function upsertRows(array $rows, bool $additive = false): void
    {
        if (!$rows) {
            return;
        }

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'product_id' => $row['product_id'],
                'sku' => $row['sku'],
                'week_start' => $row['week_start'],
                'week_end' => $row['week_end'],
                'qty_sold' => $row['qty_sold'],
            ];
        }

        $updateFields = $additive
            ? ['qty_sold' => new \Zend_Db_Expr('qty_sold + VALUES(qty_sold)')]
            : ['qty_sold'];

        $this->getConnection()->insertOnDuplicate($this->getMainTable(), $data, $updateFields);
    }
}
