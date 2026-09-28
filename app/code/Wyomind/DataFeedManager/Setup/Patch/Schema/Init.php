<?php

namespace Wyomind\DataFeedManager\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Init implements SchemaPatchInterface, PatchRevertableInterface
{
    protected $schemaSetup;


    const version = "1.0.0";

    protected $dbVersion = 0;

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        ModuleResource       $moduleResource
    ) {

        $this->schemaSetup = $schemaSetup;
        $this->dbVersion = $moduleResource->getDbVersion("Wyomind_DataFeedManager");
    }


    public function apply()
    {

        if (version_compare($this->dbVersion, self::version) >= 0) {
            $this->schemaSetup->getConnection()->startSetup();
            $installer = $this->schemaSetup;


            $datafeedmanagerTable = $installer->getConnection()
                ->newTable($installer->getTable('datafeedmanager_feeds'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Data Feed ID'
                )
                ->addColumn(
                    'name',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => ''],
                    'Data Feed Name'
                )
                ->addColumn(
                    'type',
                    Table::TYPE_INTEGER,
                    3,
                    ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                    'Type of data feed'
                )
                ->addColumn(
                    'path',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => ''],
                    'Data Feed File path'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_INTEGER,
                    1,
                    ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                    'Data feed status (enable/disable)'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Data Feed Last Update Time'
                )
                ->addColumn(
                    'store_id',
                    Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                    'Data Feed Associated Store ID'
                )
                ->addColumn(
                    'product_pattern',
                    Table::TYPE_TEXT,
                    Table::MAX_TEXT_SIZE,
                    [],
                    'Data Feed XML Item Pattern'
                )
                ->addColumn(
                    'category_filter',
                    Table::TYPE_INTEGER,
                    1,
                    ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                    'Data Feed Categories Inclusion Type'
                )
                ->addColumn(
                    'categories',
                    Table::TYPE_TEXT,
                    Table::MAX_TEXT_SIZE,
                    [],
                    'Data Feed Categories Selection'
                )
                ->addColumn(
                    'type_ids',
                    Table::TYPE_TEXT,
                    150,
                    [],
                    'Data Feed Product Types Selection'
                )
                ->addColumn(
                    'category_type',
                    Table::TYPE_INTEGER,
                    1,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Data Feed Categories Filter (product/parent)'
                )
                ->addColumn(
                    'visibilities',
                    Table::TYPE_TEXT,
                    150,
                    [],
                    'Data Feed Product Visibilities Selection'
                )
                ->addColumn(
                    'attribute_sets',
                    Table::TYPE_TEXT,
                    250,
                    ['default' => '*'],
                    'Data Feed Attribute Sets Selection'
                )
                ->addColumn(
                    'attributes',
                    Table::TYPE_TEXT,
                    Table::MAX_TEXT_SIZE,
                    [],
                    'Data Feed Advanced Filters'
                )
                ->addColumn(
                    'cron_expr',
                    Table::TYPE_TEXT,
                    900,
                    [],
                    'Data Feed Schedule Task'
                )
                ->addColumn(
                    'taxonomy',
                    Table::TYPE_TEXT,
                    150,
                    ['default' => '[default] en_US.txt'],
                    'Data Feed Taxonomies File'
                )
                ->addColumn(
                    'include_header',
                    Table::TYPE_INTEGER,
                    1,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Data Feed Categories Include Header ?'
                )
                ->addColumn(
                    'header',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Data Feed Header'
                )
                ->addColumn(
                    'footer',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Data Feed Footer'
                )
                ->addColumn(
                    'field_separator',
                    Table::TYPE_TEXT,
                    3,
                    [],
                    'Data Feed Field Separator'
                )
                ->addColumn(
                    'field_protector',
                    Table::TYPE_TEXT,
                    3,
                    [],
                    'Data Feed Field Protector'
                )
                ->addColumn(
                    'field_escape',
                    Table::TYPE_TEXT,
                    3,
                    [],
                    'Data Feed Escape Char'
                )
                ->addColumn(
                    'encoding',
                    Table::TYPE_TEXT,
                    40,
                    ['default' => 'UTF-8'],
                    'Data Feed Encoding'
                )
                ->addColumn(
                    'enclose_data',
                    Table::TYPE_INTEGER,
                    1,
                    ['default' => '1'],
                    'Data Feed Enclose Data ?'
                )
                ->addColumn(
                    'clean_data',
                    Table::TYPE_INTEGER,
                    1,
                    ['default' => '1'],
                    'Data Feed Clean Data ?'
                )
                ->addColumn(
                    'extra_header',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Data Feed Extra Header'
                )
                ->addColumn(
                    'extra_footer',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Data Feed Extra Footer'
                )
                ->addColumn(
                    'dateformat',
                    Table::TYPE_TEXT,
                    50,
                    ['default' => "{f}"],
                    'Data Feed Extra Header'
                )
                ->addColumn(
                    'ftp_enabled',
                    Table::TYPE_INTEGER,
                    1,
                    ['default' => '0'],
                    'Data Feed Enabled Ftp'
                )
                ->addColumn(
                    'use_sftp',
                    Table::TYPE_INTEGER,
                    1,
                    ['default' => '0'],
                    'Data Feed Use Sftp ?'
                )
                ->addColumn(
                    'ftp_host',
                    Table::TYPE_TEXT,
                    300,
                    [],
                    'Data Feed Ftp Host'
                )
                ->addColumn(
                    'ftp_port',
                    Table::TYPE_TEXT,
                    5,
                    [],
                    'Data Feed Ftp Port'
                )
                ->addColumn(
                    'ftp_password',
                    Table::TYPE_TEXT,
                    300,
                    [],
                    'Data Feed Ftp Password'
                )
                ->addColumn(
                    'ftp_login',
                    Table::TYPE_TEXT,
                    300,
                    [],
                    'Data Feed Ftp Login'
                )
                ->addColumn(
                    'ftp_active',
                    Table::TYPE_INTEGER,
                    1,
                    ['default' => '0'],
                    'Data Feed Ftp Active Mode'
                )
                ->addColumn(
                    'ftp_dir',
                    Table::TYPE_TEXT,
                    300,
                    [],
                    'Data Feed Ftp Dir'
                )
                ->setComment('Data Feed Manager Data Feeds Table');

            $installer->getConnection()->createTable($datafeedmanagerTable);

            $datafeedmanagerFunctionsTable = $installer->getConnection()
                ->newTable($installer->getTable('datafeedmanager_functions'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Custom Function ID'
                )
                ->addColumn(
                    'script',
                    Table::TYPE_TEXT,
                    Table::MAX_TEXT_SIZE,
                    [],
                    'Custom Function Script'
                )
                ->setComment('Data Feed Manager Custom Functions Table');

            $installer->getConnection()->createTable($datafeedmanagerFunctionsTable);

            $datafeedmanagerVariablesTable = $installer->getConnection()
                ->newTable($installer->getTable('datafeedmanager_variables'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Data Feed ID'
                )
                ->addColumn(
                    'name',
                    Table::TYPE_TEXT,
                    100,
                    [],
                    'Custom Variable Name'
                )
                ->addColumn(
                    'comment',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Custom Variable Comment'
                )
                ->addColumn(
                    'script',
                    Table::TYPE_TEXT,
                    Table::MAX_TEXT_SIZE,
                    [],
                    'Custom Variable Script'
                )
                ->setComment('Data Feed Manager Custom Variables Table');

            $installer->getConnection()->createTable($datafeedmanagerVariablesTable);

            $this->schemaSetup->getConnection()->endSetup();
        }
    }


    /**
     * @inheritdoc
     */
    public function revert()
    {
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
