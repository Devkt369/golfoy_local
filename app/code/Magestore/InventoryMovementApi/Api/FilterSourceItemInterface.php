<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Api;

use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Service for filter source item
 *
 * @api
 */
interface FilterSourceItemInterface
{
    /**
     * Get source item by sku and source code
     *
     * @param array $skus
     * @param array $sourceCodes
     * @return SourceItemInterface[]
     */
    public function getBySkusAndSourceCodes(array $skus, array $sourceCodes): array;
}
