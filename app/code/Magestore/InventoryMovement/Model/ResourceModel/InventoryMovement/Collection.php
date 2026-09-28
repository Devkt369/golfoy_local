<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model\ResourceModel\InventoryMovement;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magestore\InventoryMovement\Model\InventoryMovement as InventoryMovementModel;
use Magestore\InventoryMovement\Model\ResourceModel\InventoryMovement as InventoryMovementResourceModel;

/**
 * Class Collection
 *
 * Used for InventoryMovement collection
 */
class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            InventoryMovementModel::class,
            InventoryMovementResourceModel::class
        );
    }
}
