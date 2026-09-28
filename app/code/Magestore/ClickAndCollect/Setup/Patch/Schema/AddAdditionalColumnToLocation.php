<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Schema patch to add column to location table
 */
class AddAdditionalColumnToLocation implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * AddAdditionalColumnToLocation constructor.
     *
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

        $webposLocation = $setup->getTable('webpos_location');
        if ($setup->getConnection()->isTableExists($webposLocation)) {
            $setup->getConnection()->addColumn(
                $webposLocation,
                'is_allow_pickup',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'unsigned' => true,
                    'length' => 2,
                    'default' => 0,
                    'comment' => 'Is Allow Pickup'
                ]
            );
            $setup->getConnection()->addColumn(
                $webposLocation,
                'schedule_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'unsigned' => true,
                    'length' => null,
                    'comment' => 'Schedule Id'
                ]
            );
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    'webpos_location',
                    '	schedule_id',
                    'clickandcollect_schedule',
                    'schedule_id'
                ),
                $setup->getTable('webpos_location'),
                'schedule_id',
                $setup->getTable('clickandcollect_schedule'),
                'schedule_id',
                Table::ACTION_SET_NULL
            );
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    'clickandcollect_location_specialday',
                    '	location_id',
                    'webpos_location',
                    'location_id'
                ),
                $setup->getTable('clickandcollect_location_specialday'),
                'location_id',
                $setup->getTable('webpos_location'),
                'location_id',
                Table::ACTION_CASCADE
            );
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    'sales_order',
                    '	location_pickup_id',
                    'webpos_location',
                    'location_id'
                ),
                $setup->getTable('sales_order'),
                'location_pickup_id',
                $setup->getTable('webpos_location'),
                'location_id',
                Table::ACTION_SET_NULL
            );
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    'quote_address',
                    '	location_pickup_id',
                    'webpos_location',
                    'location_id'
                ),
                $setup->getTable('quote_address'),
                'location_pickup_id',
                $setup->getTable('webpos_location'),
                'location_id',
                Table::ACTION_SET_NULL
            );
        }
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
