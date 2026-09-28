<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer;

/**
 * Class BranchRequest ReceiveProduct
 */
class ReceiveProduct extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * ReceiveProduct construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('os_branchrequest_receive_product', 'receive_product_id');
    }
}
