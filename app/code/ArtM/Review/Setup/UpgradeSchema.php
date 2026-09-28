<?php
namespace ArtM\review\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Upgrade the Catalog module DB scheme
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
          /**
          * Create table 'greeting_message'
          */
        
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
             
              
		$installer = $setup;
		$installer->startSetup();
		if (!$installer->tableExists('artm_review_cron')) {
			$table = $installer->getConnection()->newTable(
				$installer->getTable('artm_review_cron')
			)
              ->addColumn(
                  'id',
                  \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                  null,
                  ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                  'ID'
              )
              ->addColumn(
                  'orderid',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  255,
                  ['nullable' => false],
                    'Order Id'
              )
              ->addColumn(
                  'order_source',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  100,
                  ['nullable' => false],
                    'Order Source'
              )
                
              ->addColumn(
                  'order_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                  null,
					['nullable' => false],
                    'Order created at'
              )
                
              ->addColumn(
                  'mail_status',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  500,
                  ['nullable' => false],
                    'Mail Status'
              )
              ->addColumn(
                  'prdids',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  100,
                  ['nullable' => false],
                    'Product Ids'
              )
              ->addColumn(
                  'hashid',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  255,
                  ['nullable' => false],
                    'Hash Id'
              )
              ->addColumn(
                  'customerid',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  50,
                  ['nullable' => false],
                    'Customer Id'
              )
              ->addColumn(
                  'customername',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  255,
                  ['nullable' => false],
                    'Customer Name'
              )
              ->addColumn(
                  'customeremail',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  255,
                  ['nullable' => false],
                    'Customer Email'
              )
              ->addColumn(
                  'reviewed',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  100,
                  ['nullable' => false],
                    'Reviewed'
              )
              ->addColumn(
                  'clicked',
                  \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  100,
                  ['nullable' => false],
                    'Clicked'
              )
              ->addColumn(
                  'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                  null,
					['nullable' => false],
                    'created At'
              )
              ->addColumn(
                  'sent_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                  null,
					['nullable' => false],
                    'Sent At'
              )
                
              ->setComment("Artm Review Cron");
            
            $installer->getConnection()->createTable($table);
            }
            $installer->endSetup();
        }
    }
}