<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Receive;

/**
 * Class BranchRequest Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magestore\BranchRequest\Model\InventoryTransfer\Receive::class,
            \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\Receive::class
        );
    }
}
