<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Core\Api;

/**
 * Interface SourceManagementInterface
 *
 * Source Management Service
 */
interface SourceManagementInterface
{
    /**
     * Get source items map by sku and source code
     *
     * @param string $sku
     * @param array $sourceCodes
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    public function getSourceItemsMap(string $sku, array $sourceCodes);

    /**
     * Get list product in source by source code and sku
     *
     * @param string $sourceCode
     * @param array $skus
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    public function getListProductInSource(string $sourceCode, array $skus = []);
}
