<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Api;

/**
 * Service for skip movement log
 *
 * @api
 */
interface SkipMovementLogInterface
{
    const SKIP_MOVEMENT_LOG = 'skip_movement_log';

    /**
     * Set skip movement log
     *
     * @param int $skipMovementLog
     * @return void
     */
    public function setSkipMovementLog(int $skipMovementLog): void;

    /**
     * Get skip movement log
     *
     * @return int
     */
    public function getSkipMovementLog(): int;
}
