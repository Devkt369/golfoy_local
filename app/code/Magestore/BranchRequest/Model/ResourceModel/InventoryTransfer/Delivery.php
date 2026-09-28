<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer;

/**
 * Class BranchRequest Delivery
 */
class Delivery extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Delivery construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('os_branchrequest_delivery', 'delivery_id');
    }
}
