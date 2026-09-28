<?php
namespace ArtM\Analysis\Setup;

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
       
            
            $installer ->getConnection()->addColumn(
                'sales_order_item',
                'on_clearance',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default' => 0,
                    'comment' => 'On Clearance (Yes/No)'
                ]
            );
        
        $installer->endSetup();
  }
}