<?php

namespace ArtM\CustomerLastOrder\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 * @package Mageplaza\RewardPoints\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $table = $setup->getTable('customer_grid_flat');
        $setup->getConnection()->addColumn(
            $table,
            'last_order_date',
            [
                'type' => Table::TYPE_DATETIME,
                'nullable' => true,
                'comment' => 'Last Order Date'
            ]
        );
        $setup->endSetup();
    }
}
