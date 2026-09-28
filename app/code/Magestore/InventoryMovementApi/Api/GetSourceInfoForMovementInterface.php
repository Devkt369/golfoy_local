<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Api;

/**
 * Service for get source info for movement (source name)
 *
 * @api
 */
interface GetSourceInfoForMovementInterface
{
    /**
     * Execute service
     *
     * @param array $movements
     * @return array
     */
    public function execute(array $movements): array;
}
