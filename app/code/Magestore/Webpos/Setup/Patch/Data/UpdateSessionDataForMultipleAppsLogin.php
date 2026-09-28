<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Multiple apps login - Update session data
 */
class UpdateSessionDataForMultipleAppsLogin implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * UpdateSessionDataForMultipleAppsLogin constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
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

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $webposSessionTable = $this->moduleDataSetup->getTable('webpos_session');
        if ($this->moduleDataSetup->getConnection()->tableColumnExists($webposSessionTable, 'app')) {
            $this->moduleDataSetup->getConnection()->update(
                $webposSessionTable,
                [
                    'app' => new \Zend_Db_Expr(
                        "IF (location_id IS NULL AND pos_id IS NULL, 'click_and_collect', 'pos')"
                    )
                ]
            );
        }
        return $this;
    }
}
