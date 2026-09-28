<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPointy\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Schema patch to update pos_location and sales_invoice
 */
class UpdatePointyDataToIntegration implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * UpdatePointyDataToIntegration constructor.
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $setup = $this->schemaSetup;

        $webposLocationTable = $setup->getTable('webpos_location');
        if ($setup->getConnection()->isTableExists($webposLocationTable)) {
            $setup->getConnection()->addColumn(
                $webposLocationTable,
                'pointy_enable',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'default' => 0,
                    'length' => 2,
                    'comment' => 'pointy enable'
                ]
            );
            $setup->getConnection()->addColumn(
                $webposLocationTable,
                'pointy_retailer_auth_key',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 64,
                    'comment' => 'pointy retailer auth key'
                ]
            );
            $setup->getConnection()->addColumn(
                $webposLocationTable,
                'pointy_last_submit_inventory',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => true,
                    'comment' => 'pointy last submit inventory'
                ]
            );
            $setup->getConnection()->addColumn(
                $webposLocationTable,
                'pointy_last_submit_sales',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => true,
                    'comment' => 'pointy last submit sales'
                ]
            );
            $setup->getConnection()->addColumn(
                $webposLocationTable,
                'pointy_shop_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'length' => 11,
                    'unsigned' => true,
                    'comment' => 'pointy shop id'
                ]
            );
        }

        $salesInvoiceTable = $setup->getTable('sales_invoice');
        $setup->getConnection()->addColumn(
            $salesInvoiceTable,
            'pos_location_id',
            [
                'type' => Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => true,
                'comment' => 'Pos Location ID'
            ]
        );

        $this->schemaSetup->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
