<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Migrate log resource model
 */
class Log extends AbstractDb
{
    /**#@+
     * Constants related to specific db layer
     */
    const TABLE_NAME = 'clickandcollect_migrate_log';
    const ID_FIELD_NAME = 'log_id';
    /**#@-*/

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD_NAME);
    }
}
