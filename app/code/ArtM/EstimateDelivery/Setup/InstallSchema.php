<?php

namespace ArtM\EstimateDelivery\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer->getConnection()->newTable(
            $installer->getTable('artm_delivery_estimation_cache')
        )->addColumn(
            'cache_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Cache ID'
        )->addColumn(
            'product_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Product ID'
        )->addColumn(
            'product_type',
            Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Product Type (simple/configurable)'
        )->addColumn(
            'sku',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Product SKU'
        )->addColumn(
            'simple_product_ids',
            Table::TYPE_TEXT,
            '2M',
            ['nullable' => true],
            'Simple Product IDs (JSON for configurable)'
        )->addColumn(
            'dispatch_days',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Dispatch Days'
        )->addColumn(
            'delivery_days',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Delivery Days'
        )->addColumn(
            'total_days',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Total Days (dispatch + delivery)'
        )->addColumn(
            'express_shipping',
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 0],
            'Express Shipping Available'
        )->addColumn(
            'min_delivery_days',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Min Delivery Days (for configurable)'
        )->addColumn(
            'max_delivery_days',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Max Delivery Days (for cart calculations)'
        )->addColumn(
            'calculated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Calculated At'
        )->addColumn(
            'is_valid',
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 1],
            'Is Valid'
        )->addIndex(
            $installer->getIdxName('artm_delivery_estimation_cache', ['product_id']),
            ['product_id']
        )->addIndex(
            $installer->getIdxName('artm_delivery_estimation_cache', ['sku']),
            ['sku']
        )->addIndex(
            $installer->getIdxName('artm_delivery_estimation_cache', ['calculated_at']),
            ['calculated_at']
        )->setComment(
            'ArtM Delivery Estimation Cache Table'
        );

        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}