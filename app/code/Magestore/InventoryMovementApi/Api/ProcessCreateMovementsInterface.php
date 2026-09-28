<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Api;

/**
 * Service for save movements to database
 *
 * @api
 */
interface ProcessCreateMovementsInterface
{
    /**
     * Execute service
     *
     * @param array $movements
     * @return void
     */
    public function execute(array $movements): void;
}
