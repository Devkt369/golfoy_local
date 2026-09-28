<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Remove Foreign Key of schedule id from webpos_location
 */
class RemoveForeignKeyBetweenLocationAndSchedule implements SchemaPatchInterface
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
            $setup->getConnection()->dropForeignKey(
                $webposLocation,
                $setup->getFkName(
                    'webpos_location',
                    '	schedule_id',
                    'clickandcollect_schedule',
                    'schedule_id'
                )
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
        return [AddAdditionalColumnToLocation::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
