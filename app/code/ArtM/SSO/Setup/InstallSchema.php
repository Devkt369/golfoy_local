<?php
namespace ArtM\SSO\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // Create table 'artm_sms_keys'
        $table = $installer->getConnection()->newTable(
            $installer->getTable('artm_sms_keys')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'application_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            ['nullable' => false],
            'Application ID'
        )->addColumn(
            'application_token',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            200,
            ['nullable' => false],
            'Application Token'
        )->addColumn(
            'unicode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Unicode'
        )->addColumn(
            'sender_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Sender ID'
        )->addColumn(
            'sender_id_value',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Sender ID Value'
        )->addColumn(
            'country',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'Country'
        )->addColumn(
            'url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            500,
            ['nullable' => false],
            'URL'
        )->setComment(
            'ArtM SSO SMS Keys Table'
        );

        $installer->getConnection()->createTable($table);

        // Insert sample data
        $installer->getConnection()->insertMultiple(
            $installer->getTable('artm_sms_keys'),
            [
                [
                    'application_id' => '23731',
                    'application_token' => 'e7f16OgLUwT427LmCoI81ikBrRL3F5FOTFafGEpHShOiS7kd2B',
                    'unicode' => 'yes',
                    'sender_id' => 'gText',
                    'sender_id_value' => 'Golfoy',
                    'country' => 'IN',
                    'url' => 'https://portal.bulkgate.com/api/1.0/simple/transactional',
                ]
            ]
        );

        $table = $installer->getConnection()->newTable(
            $installer->getTable('artm_loginbyotp')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'sent_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => false],
            'Sent At'
        )->addColumn(
            'otp',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            10,
            ['nullable' => false],
            'OTP'
        )->addColumn(
            'phonenumber',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            12,
            ['nullable' => false],
            'Phone Number'
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            15,
            ['nullable' => false],
            'Customer ID'
        )->setComment(
            'ArtM Login by OTP Table'
        );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
