<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model\DataProvider;

use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;
use Magento\Framework\DataObject;

/**
 * Skip movement log service
 *
 * @api
 */
class SkipMovementLog extends DataObject implements SkipMovementLogInterface
{
    /**
     * Set skip movement log
     *
     * @param int $skipMovementLog
     * @return void
     */
    public function setSkipMovementLog(int $skipMovementLog): void
    {
        $this->setData(self::SKIP_MOVEMENT_LOG, $skipMovementLog);
    }

    /**
     * Get skip movement log
     *
     * @return int
     */
    public function getSkipMovementLog(): int
    {
        return $this->getData(self::SKIP_MOVEMENT_LOG) ? 1 : 0;
    }
}
