<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * "Sync Now" used to resume from a date, now it resumes from an order id, and
 * resyncs are additive (they add to a week's qty_sold instead of replacing it -
 * see WeeklySales::upsertRows()). Any environment that already synced data under
 * the old scheme needs last_order_id seeded to whatever was already covered, or
 * the first order-id-based sync would re-aggregate that same history and add it
 * on top of what's already stored, double counting it.
 */
class SeedLastSyncedOrderId implements DataPatchInterface
{
    private const CONFIG_PATH_LAST_ORDER_ID = 'artm_inventoryplanner/general/last_order_id';

    private ModuleDataSetupInterface $moduleDataSetup;

    private WriterInterface $configWriter;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup, WriterInterface $configWriter)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();

        $weeklySalesTable = $this->moduleDataSetup->getTable('artm_inventoryplanner_weekly_sales');
        $lastWeekEnd = $connection->fetchOne(
            $connection->select()->from($weeklySalesTable, ['max_week_end' => new \Zend_Db_Expr('MAX(week_end)')])
        );

        if ($lastWeekEnd) {
            $orderTable = $this->moduleDataSetup->getTable('sales_order');
            $lastOrderId = (int) $connection->fetchOne(
                $connection->select()
                    ->from($orderTable, ['max_id' => new \Zend_Db_Expr('MAX(entity_id)')])
                    ->where('created_at <= ?', $lastWeekEnd . ' 23:59:59')
            );

            if ($lastOrderId > 0) {
                $this->configWriter->save(self::CONFIG_PATH_LAST_ORDER_ID, (string) $lastOrderId);
            }
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
