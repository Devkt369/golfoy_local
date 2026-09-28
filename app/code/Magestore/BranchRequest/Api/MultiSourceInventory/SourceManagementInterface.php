<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Api\MultiSourceInventory;

/**
 * Interface SourceManagementInterface
 */
interface SourceManagementInterface
{
    /**
     * Get Source Items Map
     *
     * @param string $sku
     * @param array $sourceCodes
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    public function getSourceItemsMap($sku, $sourceCodes);

    /**
     * Get List Product InSource
     *
     * @param string $sourceCode
     * @param array $skus
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    public function getListProductInSource($sourceCode, $skus = []);
}
