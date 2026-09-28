<?php
namespace ArtM\RecommendedFilter\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{
	public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
  {
    $installer = $setup;
    $installer->startSetup();

    // table artm_sorting_cron_log
    $tableName = $installer->getTable('artm_sorting_cron_log');      
      if ($installer->getConnection()->isTableExists($tableName) != true) {
        $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )
        ->addColumn(
            'attribute_set_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            ['nullable' => false, 'default' => 0],
              'attribute_set_id'
        )        
        ->addColumn(
          'cron_start_date_time',
          \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,         
          null,
          [],
          'cron_start_date_time'
        ) 
        ->addColumn(
          'status',
          \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
          255,
          [],
          'status'
        )
        ->addColumn(
          'cron_last_updated_date_time',
          \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
          null,
          [],
          'cron_last_updated_date_time'
        ) ;     
        $installer->getConnection()->createTable($table);
      }


      // table artm_sorting_products
      $tableName = $installer->getTable('artm_sorting_products');      
      if ($installer->getConnection()->isTableExists($tableName) != true) {
        $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )
        ->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            ['nullable' => false],
              'product_id'
        )
        ->addColumn(
          'attr_set_id',
          \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
          11,
          ['nullable' => false, 'default' => 0],
            'attr_set_id'
        )
        ->addColumn(
          'attr_set_name',
          \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
          255,
          [],
          'attr_set_name'
        )
        ->addColumn(
          'cat_id',
          \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
          255,
          [],
          'cat_id'
        )
        ->addColumn(
          'available_size_roll_value',
          \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
          '255',
          [],
          'available_size_roll_value'
        )
        ->addColumn(
          'size_roll_points',
          \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
          '10,2',
          ['nullable' => false, 'default' => 0],
            'size_roll_points'
        )
        ->addColumn(
          'high_discount_points',
          \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
          '10,2',
          ['nullable' => false, 'default' => 0],
            'high_discount_points'
        )
        ->addColumn(
          'latest_updated_points',
          \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
          '10,2',
          ['nullable' => false, 'default' => 0],
            'latest_updated_points'
        )
        ->addColumn(
          'availability_cgc',
          \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
          11,
          ['nullable' => false, 'default' => 0],
            'availability_cgc'
        )
        ->addColumn(
          'availability_other',
          \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
          11,
          ['nullable' => false, 'default' => 0],
            'availability_other '
        )
        ->addColumn(
          'total_points',
          \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
          '10,2',
          ['nullable' => false, 'default' => 0],
            'total_points'
        )
        ->addColumn(
          'updated_at',
          \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
          null,
          ['nullable' => false, 'default' => new \Zend_Db_Expr('CURRENT_TIMESTAMP')],                                                                                   
          'updated_at'
        ) ;   
          $installer->getConnection()->createTable($table);
        }
      $installer->endSetup();
  }
}