<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Api\Data;

/**
 * Search results of Repository::getList method
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface InventoryMovementSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get inventory movement list
     *
     * @return \Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface[]
     */
    public function getItems();

    /**
     * Set inventory movement list
     *
     * @param \Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
