<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Api;

/**
 * Service for get area and url of request
 *
 * @api
 */
interface GetAreaAndUrlInterface
{
    /**
     * Execute service
     *
     * @return array
     */
    public function execute(): array;
}
