<?php

namespace ArtM\TradeIn\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $connection = $setup->getConnection();
        $table = $setup->getTable('artm_tradein');

        if (!$connection->tableColumnExists($table, 'customer_expected_price')) {
            $connection->addColumn($table, 'customer_expected_price', [
                'type' => Table::TYPE_TEXT,
                'length' => 50,
                'nullable' => true,
                'default' => null,
                'comment' => 'Customer Expected Price',
            ]);
        }

        if (!$connection->tableColumnExists($table, 'our_offered_price')) {
            $connection->addColumn($table, 'our_offered_price', [
                'type' => Table::TYPE_TEXT,
                'length' => 50,
                'nullable' => true,
                'default' => null,
                'comment' => 'Our Offered Price',
            ]);
        }

        if (!$connection->tableColumnExists($table, 'comment_history')) {
            $connection->addColumn($table, 'comment_history', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'default' => null,
                'comment' => 'Comment History',
            ]);
        }

        if (!$connection->tableColumnExists($table, 'is_listed')) {
            $connection->addColumn($table, 'is_listed', [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'unsigned' => true,
                'comment' => 'Is Listed (1=Yes, 0=No)',
            ]);
        }

        if (!$connection->tableColumnExists($table, 'show_customer_expected_price')) {
            $connection->addColumn($table, 'show_customer_expected_price', [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'unsigned' => true,
                'comment' => 'Show Customer Expected Price on Frontend (1=Yes, 0=No)',
            ]);
        }

        if (!$connection->tableColumnExists($table, 'show_our_offered_price')) {
            $connection->addColumn($table, 'show_our_offered_price', [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'unsigned' => true,
                'comment' => 'Show Our Offered Price on Frontend (1=Yes, 0=No)',
            ]);
        }

        $setup->endSetup();
    }
}
