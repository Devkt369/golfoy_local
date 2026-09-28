<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model\ResourceModel\OrderPotentialError;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magestore\InventoryMovement\Model\OrderPotentialError as OrderPotentialErrorModel;
use Magestore\InventoryMovement\Model\ResourceModel\OrderPotentialError as OrderPotentialErrorResourceModel;

/**
 * Class Collection
 *
 * Used for OrderPotentialError collection
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
            OrderPotentialErrorModel::class,
            OrderPotentialErrorResourceModel::class
        );
    }
}
