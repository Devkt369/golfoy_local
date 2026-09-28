<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class InventorySnapshot extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('artm_inventoryplanner_inventory_snapshot', 'id');
    }

    /**
     * One row per product - re-syncing a product replaces its previous snapshot
     * entirely, since this always represents "inventory as of the last time this
     * product was part of a sync", not a history.
     *
     * @param array<int, array{product_id:int, sku:string, total_salable_qty:float, total_selected_source_qty:float, source_breakdown:string, calculated_at:string}> $rows
     */
    public function upsertSnapshots(array $rows): void
    {
        if (!$rows) {
            return;
        }

        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            $rows,
            ['sku', 'total_salable_qty', 'total_selected_source_qty', 'source_breakdown', 'calculated_at']
        );
    }

    /**
     * @param int[] $productIds
     * @return array<int, array<string, mixed>> product_id => row
     */
    public function getSnapshotsForProducts(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('product_id IN (?)', $productIds);

        $snapshots = [];
        foreach ($connection->fetchAll($select) as $row) {
            $snapshots[(int) $row['product_id']] = $row;
        }

        return $snapshots;
    }
}
