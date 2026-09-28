<?php

namespace ArtM\CancelOrder\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

	public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();
		if (!$installer->tableExists('cancel_order_custom')) {
			$table = $installer->getConnection()->newTable(
				$installer->getTable('cancel_order_custom')
			)
				->addColumn(
					'id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						'identity' => true,
						'nullable' => false,
						'primary'  => true,
						'unsigned' => true,
					],
					' ID'
				)
                                ->addColumn(
					'order_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						
						'nullable' => false,
                                                'unsigned' => true,					
					],
					'ORDER ID'
				)
                   ->addColumn(
					'customer_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						
						'nullable' => false,
                                                'unsigned' => true,					
					],
					'CUSTOMER ID'
				)             
				->addColumn(
					'order_state',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'ORDER State'
				)
				->addColumn(
					'order_status',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'order_status'
				)
				->addColumn(
					'live_status',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'live_status'
				)
				->addColumn(
					'live_state',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'live_state'
				)
				->addColumn(
					'return_cancel_status',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'return_cancel_status'
				)
				->addColumn(
					'return_reason',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					500,
					[],
					'return_reason'
				)
				->addColumn(
					'customer_name',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					500,
					[],
					'customer_name'
				)
				->addColumn(
					'customer_email',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					500,
					[],
					'customer_email'
				)
				->addColumn(
					'customer_phone',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					500,
					[],
					'customer_phone'
				)
				->addColumn(
					'product_ids',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					500,
					[],
					'product_ids'
				)



				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
					'Created At'
				)->addColumn(
					'updated_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
					'Updated At')
				->setComment('Post Table');
			$installer->getConnection()->createTable($table);

			$installer->getConnection()->addIndex(
				$installer->getTable('cancel_order_custom'),
				$setup->getIdxName(
					$installer->getTable('cancel_order_custom'),
					[ 'order_state', 'order_status','live_state','live_status','return_cancel_status', 'created_at', 'updated_at','return_reason','customer_name','customer_email','customer_phone','product_ids'],
					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
				),
				[ 'order_state', 'order_status', 'live_state','live_status','return_cancel_status','created_at', 'updated_at','return_reason','customer_name','customer_email','customer_phone','product_ids'],
				\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
			);
		}
		$installer->endSetup();
	}
}