<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Makm\Opsell\Setup;

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
                  null,
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
    }
}