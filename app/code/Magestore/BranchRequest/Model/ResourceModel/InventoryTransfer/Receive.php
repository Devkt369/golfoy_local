<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer;

/**
 * Class BranchRequest Receive
 */
class Receive extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Receive construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('os_branchrequest_receive', 'receive_id');
    }
}
