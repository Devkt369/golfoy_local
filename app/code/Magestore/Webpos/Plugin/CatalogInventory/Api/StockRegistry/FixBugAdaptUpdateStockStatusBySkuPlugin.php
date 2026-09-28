<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * Webpos - FixBugAdaptUpdateStockStatusBySkuPlugin
 */
class FixBugAdaptUpdateStockStatusBySkuPlugin
{
    /**
     * After Update Stock Item By Sku
     *
     * @param StockRegistryInterface $subject
     * @param int|null $itemId
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateStockItemBySku(
        StockRegistryInterface $subject,
        $itemId
    ) {
        return $itemId ?? 0;
    }
}
