<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\ResourceModel;

/**
 * Class BranchRequest InventoryTransfer
 */
class InventoryTransfer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * InventoryTransfer construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('os_branchrequest', 'inventorytransfer_id');
    }
}
