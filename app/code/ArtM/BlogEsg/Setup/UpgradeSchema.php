<?php
namespace ArtM\BlogEsg\Setup;;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
	public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
		$installer = $setup;

		$installer->startSetup();

		if(version_compare($context->getVersion(), '1.4.0', '<')) {
			
              $table = $setup->getConnection()
                ->newTable($setup->getTable('artm_blogesg'))
                ->addColumn(
                      'id',
                      \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                      null,
                      ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                      'ID'
                  )
                  ->addColumn(
                      'attributesetname',
                      \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                      100,
                      ['nullable' => false],
                        'Attribute Set Name'
                  )
                  ->addColumn(
                      'bloglink',
                      \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                      2000,
                      ['nullable' => false],
                        'Blog Link'
                  )
                  ->addColumn(
                      'uppertext',
                      \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                      200,
                      ['nullable' => false],
                        'Upper Text'
                  )
                  ->addColumn(
                      'bottomtext',
                      \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                      200,
                      ['nullable' => false],
                        'Bottom Text'
                  )
                  ->addColumn(
                      'created_at',
                      \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                      null,
                      ['nullable' => false],
                        'Created At'
                  )
                  ->setComment("Blog Esg for product page");
           



		$installer->endSetup();
	}
}
}