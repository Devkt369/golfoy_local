<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Api\Migrate;

/**
 * Service Interface - Migrate Order
 */
interface MigrateOrderServiceInterface
{
    /**
     * Migrate order
     */
    public function migrate();
}
