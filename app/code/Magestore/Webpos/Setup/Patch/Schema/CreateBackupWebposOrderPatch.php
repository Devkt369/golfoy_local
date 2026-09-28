<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magestore\Webpos\Model\ResourceModel\Checkout\PosOrder as PosOrderResource;

/**
 * Class WebposOrderBackupPatch
 *
 * Used to move order complete from webpos order table to backup table when install module webpos
 */
class CreateBackupWebposOrderPatch implements SchemaPatchInterface
{
    /**
     * @var PosOrderResource
     */
    private $posOrderResource;

    /**
     * @param PosOrderResource $posOrderResource
     */
    public function __construct(
        PosOrderResource $posOrderResource
    ) {
        $this->posOrderResource = $posOrderResource;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        // Create table backup
        $this->posOrderResource->createBackupOfWebposOrderTable();

        // Delete all order complete before two month ago
        $this->posOrderResource->deleteWebposOrderBeforeTwoMonthAgo();

        // Move data from webpos order to backup after two month ago
        $this->posOrderResource->moveToBackupOfWebposOrderByBulk();

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
