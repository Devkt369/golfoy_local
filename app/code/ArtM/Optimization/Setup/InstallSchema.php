<?php
namespace ArtM\Optimization\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('artm_product_cache')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('artm_product_cache')
            )
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                    'ID'
                )
                ->addColumn(
                    'product_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'unsigned' => true],
                    'Product ID'
                )
                ->addColumn(
                    'attibutes_html',
                    Table::TYPE_TEXT,
                    '64k',
                    [],
                    'Attributes HTML'
                )
                ->addColumn(
                    'type',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Type'
                )
                ->addColumn(
                    'price_html',
                    Table::TYPE_TEXT,
                    '64k',
                    [],
                    'Price HTML'
                )
                ->addColumn(
                    'tier_price_html',
                    Table::TYPE_TEXT,
                    '64k',
                    [],
                    'Tier Price HTML'
                )
                ->addColumn(
                    'size_role',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Size Role'
                )
                ->addColumn(
                    'product_tag',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Product Tag'
                )
                ->addColumn(
                    'last_modify',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Last Modify'
                )
                ->setComment('ArtM Product Cache Table');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
