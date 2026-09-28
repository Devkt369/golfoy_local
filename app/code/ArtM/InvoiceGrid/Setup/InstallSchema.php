<?php
namespace ArtM\InvoiceGrid\Setup;

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

    // table artm_invoices
    $tableName = $installer->getTable('artm_invoices');      
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
          'invoice',
          \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
          255,
          [],
          'invoice'
        )        
        ->addColumn(
          'created_at',
          \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
          null,
          ['nullable' => false, 'default' => new \Zend_Db_Expr('CURRENT_TIMESTAMP')],                                                                                   
          'created_at'
        ) ;   
          $installer->getConnection()->createTable($table);
        }
      $installer->endSetup();
  }
}