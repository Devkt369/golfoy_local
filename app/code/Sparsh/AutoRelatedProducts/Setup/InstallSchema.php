<?php
/**
 * Class InstallSchema
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AutoRelatedProducts\Setup;

/**
 * Class InstallSchema
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        // sparsh_auto_related_products_rule table
        if (!$installer->tableExists('sparsh_auto_related_products_rule')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('sparsh_auto_related_products_rule')
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Rule ID'
            )->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false,'default' => 1],
                'Status'
            )->addColumn(
                'rule_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Rule Name'
            )->addColumn(
                'rule_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Rule Type'
            )->addColumn(
                'position',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Position'
            )->addColumn(
                'priority',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => true,'default' => 1],
                'Priority'
            )->addColumn(
                'start_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                null,
                [],
                'Start Date'
            )->addColumn(
                'end_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                null,
                [],
                'End Date'
            )->addColumn(
                'conditions_serialized',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Conditions Serialized'
            )->addColumn(
                'category_ids',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Category Ids'
            )->addColumn(
                'block_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Block Name'
            )->addColumn(
                'display_mode',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false],
                'Display Mode'
            )->addColumn(
                'display_layout',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false],
                'Display Layout'
            )->addColumn(
                'product_order',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false],
                'Product Order'
            )->addColumn(
                'no_of_products',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['nullable' => false],
                'No Of Products'
            )->addColumn(
                'display_out_of_stock',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false,'default' => 0],
                'Display Out Of Stock'
            )->addColumn(
                'additional_info',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Additional Information'
            )->addColumn(
                'add_products',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Add Products'
            )->addColumn(
                'not_display_products',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Not Display Products'
            )->addColumn(
                'actions_serialized',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Actions Serialized'
            )->setComment('Sparsh Auto Related Products Rule Table');
            $installer->getConnection()->createTable($table);

            $installer->getConnection()->addIndex(
                $installer->getTable('sparsh_auto_related_products_rule'),
                $setup->getIdxName(
                    $installer->getTable('sparsh_auto_related_products_rule'),
                    ['rule_name']
                ),
                ['rule_name']
            );
            $installer->getConnection()->addIndex(
                $installer->getTable('sparsh_auto_related_products_rule'),
                $setup->getIdxName(
                    $installer->getTable('sparsh_auto_related_products_rule'),
                    ['rule_type']
                ),
                ['rule_type']
            );
            $installer->getConnection()->addIndex(
                $installer->getTable('sparsh_auto_related_products_rule'),
                $setup->getIdxName(
                    $installer->getTable('sparsh_auto_related_products_rule'),
                    ['position']
                ),
                ['position']
            );
            $installer->getConnection()->addIndex(
                $installer->getTable('sparsh_auto_related_products_rule'),
                $setup->getIdxName(
                    $installer->getTable('sparsh_auto_related_products_rule'),
                    ['block_name']
                ),
                ['block_name']
            );
        }

        // sparsh_auto_related_products_rule_store table
        if (!$installer->tableExists('sparsh_auto_related_products_rule_store')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('sparsh_auto_related_products_rule_store')
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => false,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Rule ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => false,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Store ID'
            )->addForeignKey(
                $installer->getFkName(
                    'sparsh_auto_related_products_rule_store_foreign_key',
                    'rule_id',
                    'sparsh_auto_related_products_rule',
                    'rule_id'
                ),
                'rule_id',
                $installer->getTable('sparsh_auto_related_products_rule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Sparsh Auto Related Products Rule Store Table');
            $installer->getConnection()->createTable($table);
        }

        // sparsh_auto_related_products_rule_group table
        if (!$installer->tableExists('sparsh_auto_related_products_rule_group')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('sparsh_auto_related_products_rule_group')
            )->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => false,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Rule ID'
            )->addColumn(
                'customer_group_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => false,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Customer Group ID'
            )->addForeignKey(
                $installer->getFkName(
                    'sparsh_auto_related_products_rule_group_foreign_key',
                    'rule_id',
                    'sparsh_auto_related_products_rule',
                    'rule_id'
                ),
                'rule_id',
                $installer->getTable('sparsh_auto_related_products_rule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Sparsh Auto Related Products Rule Group Table');
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
