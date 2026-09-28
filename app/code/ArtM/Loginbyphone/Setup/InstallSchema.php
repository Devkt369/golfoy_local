<?php
/**
* Copyright © 2016 Magento. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Makm\Opsell\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
    * {@inheritdoc}
    * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
    */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
          $table = $setup->getConnection()
              ->newTable($setup->getTable('artm_loginbyphone'))
              ->addColumn(
                  'id',
                  \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                  null,
                  ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                  'ID'
              )
              ->addColumn(
                  'phonenumber',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  500,
                  ['nullable' => false],
                    'Phone Number'
              )
              ->addColumn(
                  'sent_at',
                  \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                  null,
                  ['nullable' => false],
                    'Sent At'
              )
              ->addColumn(
                  'otp',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  255,
                  ['nullable' => false, 'default' => ''],
                    'OTP'
              )->setComment("Login by Phone");
        $setup->getConnection()->createTable($table);
        
        
          $table = $setup->getConnection()
              ->newTable($setup->getTable('artm_sms_getkeys'))
              ->addColumn(
                  'id',
                  \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                  null,
                  ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                  'ID'
              ) 
              ->addColumn(
                  'application_id',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  100,
                  ['nullable' => false],
                    'application_id'
              )
              ->addColumn(
                  'application_token',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  200,
                  ['nullable' => false],
                    'application_token'
              )
              ->addColumn(
                  'unicode',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  20,
                  ['nullable' => false],
                    'unicode'
              )
              ->addColumn(
                  'sender_id',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  20,
                  ['nullable' => false],
                    'sender_id'
              )
              ->addColumn(
                  'sender_id_value',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  20,
                  ['nullable' => false],
                    'sender_id_value'
              )
              ->addColumn(
                  'country',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  20,
                  ['nullable' => false],
                    'country'
              )
              ->addColumn(
                  'url',
                  \Magento\Framework\DB\Ddl\Table::TYPE_VARCHAR,
                  500,
                  ['nullable' => false],
                    'country'
              )
              ->setComment("Artm Sms Getkeys");
            $setup->getConnection()->createTable($table);
        
        
        
    }
}
