<?php
namespace ArtM\CanonicalUrls\Setup;

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
    $tableName = $installer->getTable('artm_canonicalurls');      
      if ($installer->getConnection()->isTableExists($tableName) != true) {
        $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )
        ->addColumn(
            'categoryid',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            ['nullable' => false],
              'Category id'
        )
        ->addColumn(
            'url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            2000,
            ['nullable' => false],
              'Url'
        )                
        ->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => false],
              'Created At'
        )
        ->setComment("Canonical urls");
        $installer->getConnection()->createTable($table);
      }
      $installer->endSetup();
  }
}